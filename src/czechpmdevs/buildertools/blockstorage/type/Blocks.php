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

namespace czechpmdevs\buildertools\blockstorage\type;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\BuilderTools;
use JetBrains\PhpStorm\Pure;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use RuntimeException;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_slice;
use function array_values;
use function count;
use function pack;
use function unpack;

final class Blocks {

	protected BlockArray $blockArray;

	/** @var array<int, int> */
	protected array $coords = [];
	/** @var array<int, int> */
	protected array $blocks = [];

	protected string $compressedBlocks;
	protected string $compressedCoords;

	protected int $offset = 0;

	/**
	 * Fields to avoid allocating memory every time
	 * when writing or reading block from the array
	 */
	protected int $lastHash, $lastBlockHash;

	/**
	 * @internal
	 */
	public function __construct(BlockArray $blockArray) {
		$this->blockArray = $blockArray;
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlock(Vector3 $vector3, int $id, int $meta): self {
		return $this->addBlockAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ(), $id, $meta);
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlockAt(int $x, int $y, int $z, int $id, int $meta): self {
		$this->lastHash = World::blockHash($x, $y, $z);

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

	public function resetOffset(): void {
		$this->offset = 0;
	}

	/**
	 * @return int $size
	 */
	public function size(): int {
		return count($this->coords);
	}

	/**
	 * @return int[]
	 */
	public function getCoordsData(): array {
		return $this->coords;
	}

	/**
	 * @return int[]
	 */
	public function getBlockData(): array {
		return $this->blocks;
	}

	public function getCompressedCoords(): string {
		return $this->compressedCoords;
	}

	public function getCompressedBlocks(): string {
		return $this->compressedBlocks;
	}

	public function removeDuplicates(): void {
		if(!(BuilderTools::getConfiguration()->getBoolProperty("remove-duplicates"))) {
			return;
		}

		// TODO - Optimize this
		$blocks = array_combine(array_reverse($this->coords, true), array_reverse($this->blocks, true));

		$this->coords = array_keys($blocks);
		$this->blocks = array_values($blocks);
	}

	/**
	 * @internal
	 */
	public function compress(): void {
		/** @phpstan-var string|false $coords */
		$coords = pack("q*", ...$this->coords);
		/** @phpstan-var string|false $blocks */
		$blocks = pack("N*", ...$this->blocks);

		if($coords === false || $blocks === false) {
			throw new RuntimeException("Error whilst compressing");
		}

		$this->compressedCoords = $coords;
		$this->compressedBlocks = $blocks;

		$this->coords = [];
		$this->blocks = [];
	}

	/**
	 * @internal
	 */
	public function decompress(): void {
		/** @phpstan-var int[]|false $coords */
		$coords = unpack("q*", $this->compressedCoords);
		/** @phpstan-var int[]|false $coords */
		$blocks = unpack("N*", $this->compressedBlocks);

		if($coords === false || $blocks === false) {
			throw new RuntimeException("Error whilst decompressing");
		}

		$this->coords = array_values($coords);
		$this->blocks = array_values($blocks);

		unset($this->compressedCoords);
		unset($this->compressedBlocks);
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

	/**
	 * @internal
	 */
	public function nbtSerialize(CompoundTag $nbt): void {
		$nbt->setByteArray("Coords", $this->compressedCoords);
		$nbt->setByteArray("Blocks", $this->compressedBlocks);
	}

	/**
	 * @internal
	 */
	public function nbtDeserialize(CompoundTag $nbt): void {
		$this->compressedCoords = $nbt->getByteArray("Coords");
		$this->compressedBlocks = $nbt->getByteArray("Blocks");
	}
}