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
use pocketmine\math\Vector3;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
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
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

final class Tiles {

	protected BlockArray $blockArray;

	/** @var array<int, int> */
	protected array $coords = [];
	/** @var array<int, CompoundTag> */
	protected array $tiles = [];

	protected string $compressedTiles;
	protected string $compressedCoords;

	protected int $offset = 0;

	/**
	 * Field to avoid allocating memory every time
	 * when writing or reading tile from the array
	 */
	protected int $lastHash;

	/**
	 * @internal
	 */
	public function __construct(BlockArray $blockArray) {
		$this->blockArray = $blockArray;
	}

	/**
	 * Adds tile to the tile array
	 *
	 * @return $this
	 */
	public function addTile(Vector3 $vector3, CompoundTag $nbt): self {
		return $this->addTileAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ(), $nbt);
	}

	/**
	 * Adds tile to the tile array
	 *
	 * @return $this
	 */
	public function addTileAt(int $x, int $y, int $z, CompoundTag $nbt): self {
		$this->lastHash = World::blockHash($x, $y, $z);

		$this->coords[] = $this->lastHash;
		$this->tiles[] = $nbt;

		return $this;
	}

	/**
	 * Returns if it is possible read next tile from the array
	 */
	public function hasNext(): bool {
		return $this->offset < count($this->tiles);
	}

	/**
	 * Reads next tile in the array
	 */
	public function readNext(?int &$x, ?int &$y, ?int &$z, ?CompoundTag &$tile): void {
		$this->lastHash = $this->coords[$this->offset];
		$tile = $this->tiles[$this->offset++];

		World::getBlockXYZ($this->lastHash, $x, $y, $z);
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
	 * @return CompoundTag[]
	 */
	public function getBlockData(): array {
		return $this->tiles;
	}

	public function removeDuplicates(): void {
		if(!(BuilderTools::getConfiguration()->getBoolProperty("remove-duplicates"))) {
			return;
		}

		// TODO - Optimize this
		$tiles = array_combine(array_reverse($this->coords, true), array_reverse($this->tiles, true));

		$this->coords = array_keys($tiles);
		$this->tiles = array_values($tiles);
	}

	public function compress(): void {
		/** @phpstan-var string|false $coords */
		$coords = pack("q*", ...$this->coords);

		$serializer = new NetworkNbtSerializer();
		$tiles = zlib_encode($serializer->write(new TreeRoot(new ListTag($this->tiles))), ZLIB_ENCODING_GZIP);

		if($coords === false || $tiles === false) {
			throw new RuntimeException("Error whilst compressing");
		}

		$this->compressedCoords = $coords;
		$this->compressedTiles = $tiles;

		$this->coords = [];
		$this->tiles = [];
	}

	/**
	 * @internal
	 */
	public function decompress(): void {
		/** @phpstan-var int[]|false $coords */
		$coords = unpack("q*", $this->compressedCoords);
		$serializedTiles = zlib_decode($this->compressedTiles);

		if($coords === false || $serializedTiles === false) {
			throw new RuntimeException("Error whilst decompressing");
		}

		$deserializer = new NetworkNbtSerializer();
		$tiles = $deserializer->read($this->compressedTiles)->getTag();
		if(!$tiles instanceof ListTag) {
			throw new NbtDataException("Root tag is not a NBT_List");
		}

		/** @var array<int, CompoundTag> $nbtData */
		$nbtData = $tiles->getAllValues();

		$this->coords = array_values($coords);
		$this->tiles = $nbtData;

		unset($this->compressedCoords);
		unset($this->compressedTiles);
	}

	/**
	 * Removes all the tiles whose were checked already
	 * For cleaning duplicate cache use cancelDuplicateDetection()
	 */
	public function cleanGarbage(): void {
		$this->coords = array_slice($this->coords, $this->offset);
		$this->tiles = array_slice($this->tiles, $this->offset);

		$this->offset = 0;
	}

	/**
	 * @internal
	 */
	public function nbtSerialize(CompoundTag $nbt): void {
		$nbt->setTag("Tiles", (new CompoundTag())
			->setByteArray("Coords", $this->compressedCoords)
			->setByteArray("Tiles", $this->compressedTiles)
		);
	}

	/**
	 * @internal
	 */
	public function nbtDeserialize(CompoundTag $nbt): void {
		$biomeNbt = $nbt->getCompoundTag("Tiles");
		if($biomeNbt === null) {
			return;
		}

		$this->compressedCoords = $biomeNbt->getByteArray("Coords");
		$this->compressedTiles = $biomeNbt->getByteArray("Tiles");
	}
}