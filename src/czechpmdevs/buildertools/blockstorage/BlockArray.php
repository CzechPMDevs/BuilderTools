<?php

/**
 * Copyright (C) 2018-2021  CzechPMDevs
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace czechpmdevs\buildertools\blockstorage;

use czechpmdevs\buildertools\BuilderTools;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\ChunkManager;
use pocketmine\world\World;
use RuntimeException;
use Serializable;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function is_string;
use function pack;
use function unpack;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

class BlockArray implements UpdateLevelData, Serializable {

	/** @var int[] */
	public array $blocks = [];
	/** @var int[] */
	public array $coords = [];

	protected ?BlockArraySizeData $sizeData = null;

	protected ?ChunkManager $world = null;

	protected bool $detectDuplicates;

	protected bool $isCompressed = false;

	public string $compressedBlocks;
	public string $compressedCoords;

	public int $offset = 0;

	/**
	 * Fields to avoid allocating memory every time
	 * when writing or reading block from the
	 * array
	 */
	protected int $lastHash, $lastBlockHash;

	public function __construct(bool $detectDuplicates = false) {
		$this->detectDuplicates = $detectDuplicates;
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlock(Vector3 $vector3, int $id, int $meta): BlockArray {
		return $this->addBlockAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ(), $id, $meta);
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlockAt(int $x, int $y, int $z, int $id, int $meta): BlockArray {
		$this->lastHash = World::blockHash($x, $y, $z);

		if($this->detectDuplicates && in_array($this->lastHash, $this->coords, true)) {
			return $this;
		}

		$this->coords[] = $this->lastHash;
		$this->blocks[] = $id << 4 | $meta;

		return $this;
	}

	/**
	 * Returns if it is possible read next block from the array
	 */
	public function hasNext(): bool {
		return $this->offset < count($this->blocks);
	}

	/**
	 * Reads next block in the array
	 */
	public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$id, ?int &$meta): void {
		$this->lastHash = $this->coords[$this->offset];
		$this->lastBlockHash = $this->blocks[$this->offset++];

		World::getBlockXYZ($this->lastHash, $x, $y, $z);
		$id = $this->lastBlockHash >> 4;
		$meta = $this->lastBlockHash & 0xf;
	}

	/**
	 * @return int $size
	 */
	public function size(): int {
		return count($this->coords);
	}

	/**
	 * Adds Vector3 to all the blocks in BlockArray
	 */
	public function addVector3(Vector3 $vector3): BlockArray {
		$floorX = $vector3->getFloorX();
		$floorY = $vector3->getFloorY();
		$floorZ = $vector3->getFloorZ();

		$blockArray = new BlockArray();
		$blockArray->blocks = $this->blocks;

		foreach($this->coords as $hash) {
			World::getBlockXYZ($hash, $x, $y, $z);
			$blockArray->coords[] = World::blockHash(($floorX + $x), ($floorY + $y), ($floorZ + $z));
		}

		return $blockArray;
	}

	/**
	 * Subtracts Vector3 from all the blocks in BlockArray
	 */
	public function subtractVector3(Vector3 $vector3): BlockArray {
		return $this->addVector3($vector3->multiply(-1));
	}

	/**
	 * @return BlockArraySizeData is used for calculating dimensions
	 */
	public function getSizeData(): BlockArraySizeData {
		if($this->sizeData === null) {
			$this->sizeData = new BlockArraySizeData($this);
		}

		return $this->sizeData;
	}

	public function setWorld(?ChunkManager $level): self {
		$this->world = $level;

		return $this;
	}

	public function getWorld(): ?ChunkManager {
		return $this->world;
	}

	/**
	 * @return int[]
	 */
	public function getBlockArray(): array {
		return $this->blocks;
	}

	/**
	 * @return int[]
	 */
	public function getCoordsArray(): array {
		return $this->coords;
	}

	public function removeDuplicates(): void {
		if(!(BuilderTools::getConfiguration()->getBoolProperty("remove-duplicate-blocks"))) {
			return;
		}

		// TODO - Optimize this
		$blocks = array_combine(array_reverse($this->coords, true), array_reverse($this->blocks, true));

		$this->coords = array_keys($blocks);
		$this->blocks = array_values($blocks);
	}

	public function save(): void {
		if(BuilderTools::getConfiguration()->getBoolProperty("clipboard-compression")) {
			$this->compress();
			$this->isCompressed = true;
		}
	}

	public function load(): void {
		if(BuilderTools::getConfiguration()->getBoolProperty("clipboard-compression")) {
			$this->decompress();
			$this->isCompressed = false;
		}
	}

	public function compress(bool $cleanDecompressed = true): void {
		/** @phpstan-var string|false $coords */
		$coords = pack("q*", ...$this->coords);
		/** @phpstan-var string|false $blocks */
		$blocks = pack("N*", ...$this->blocks);

		if($coords === false || $blocks === false) {
			throw new RuntimeException("Error whilst compressing");
		}

		$this->compressedCoords = $coords;
		$this->compressedBlocks = $blocks;

		if($cleanDecompressed) {
			$this->coords = [];
			$this->blocks = [];
		}
	}

	public function decompress(bool $cleanCompressed = true): void {
		/** @phpstan-var int[]|false $coords */
		$coords = unpack("q*", $this->compressedCoords);
		/** @phpstan-var int[]|false $coords */
		$blocks = unpack("N*", $this->compressedBlocks);

		if($coords === false || $blocks === false) {
			throw new RuntimeException("Error whilst decompressing");
		}

		$this->coords = array_values($coords);
		$this->blocks = array_values($blocks);

		if($cleanCompressed) {
			unset($this->compressedCoords);
			unset($this->compressedBlocks);
		}
	}

	public function isCompressed(): bool {
		return $this->isCompressed;
	}

	/**
	 * Removes all the blocks whose were checked already
	 * For cleaning duplicate cache use cancelDuplicateDetection()
	 */
	public function cleanGarbage(): void {
		$this->coords = array_slice($this->coords, $this->offset);
		$this->blocks = array_slice($this->blocks, $this->offset);

		$this->offset = 0;
	}

	public function serialize(): ?string {
		$this->compress();

		$nbt = new CompoundTag();
		$nbt->setByteArray("Coords", $this->compressedCoords);
		$nbt->setByteArray("Blocks", $this->compressedBlocks);
		$nbt->setByte("DuplicateDetection", $this->detectDuplicates ? 1 : 0);

		$serializer = new BigEndianNbtSerializer();
		$buffer = zlib_encode($serializer->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);

		if($buffer === false) {
			return null;
		}

		return $buffer;
	}

	public function unserialize($data): void {
		if(!is_string($data)) {
			return;
		}

		if(!($data = zlib_decode($data))) {
			return;
		}

		/** @var CompoundTag $nbt */
		$nbt = (new BigEndianNbtSerializer())->read($data)->getTag();
		if(!$nbt->getTag("Coords") instanceof ByteArrayTag || !$nbt->getTag("Blocks") instanceof ByteArrayTag || !$nbt->getTag("DuplicateDetection") instanceof ByteTag) {
			return;
		}

		$this->compressedCoords = $nbt->getByteArray("Coords");
		$this->compressedBlocks = $nbt->getByteArray("Blocks");
		$this->detectDuplicates = $nbt->getByte("DuplicateDetection") == 1;

		$this->decompress();
	}
}