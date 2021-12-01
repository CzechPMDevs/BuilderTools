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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use RuntimeException;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_values;
use function count;
use function pack;
use function unpack;

final class Biomes {

	protected BlockArray $blockArray;

	/** @var array<int, int> */
	protected array $coords = [];
	/** @var array<int, int> */
	protected array $biomes = [];

	protected string $compressedBiomes;
	protected string $compressedCoords;

	protected int $offset = 0;

	/**
	 * Field to avoid allocating memory every time
	 * when writing or reading block from the array
	 */
	protected int $lastHash;

	/**
	 * @internal
	 */
	public function __construct(BlockArray $blockArray) {
		$this->blockArray = $blockArray;
	}

	/**
	 * Adds biome to the biome array
	 *
	 * @return $this
	 */
	public function addBiome(Vector3 $vector2, int $id): self {
		return $this->addBiomeAt($vector2->getFloorX(), $vector2->getFloorZ(), $id);
	}

	/**
	 * Adds biome to the biome array
	 *
	 * @return $this
	 */
	public function addBiomeAt(int $x, int $z, int $id): self {
		$this->lastHash = World::chunkHash($x, $z);

		$this->coords[] = $this->lastHash;
		$this->biomes[] = $id;

		return $this;
	}

	/**
	 * Returns if it is possible read next biome from the array
	 */
	public function hasNext(): bool {
		return $this->offset < count($this->biomes);
	}

	/**
	 * Reads next biome in the array
	 */
	public function readNext(?int &$x, ?int &$z, ?int &$id): void {
		$this->lastHash = $this->coords[$this->offset];
		$id = $this->biomes[$this->offset++];

		World::getXZ($this->lastHash, $x, $z);
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
	public function getBiomeData(): array {
		return $this->biomes;
	}

	public function removeDuplicates(): void {
		if(!(BuilderTools::getConfiguration()->getBoolProperty("remove-duplicates"))) {
			return;
		}

		// TODO - Optimize this
		$biomes = array_combine(array_reverse($this->coords, true), array_reverse($this->biomes, true));

		$this->coords = array_keys($biomes);
		$this->biomes = array_values($biomes);
	}

	/**
	 * @internal
	 */
	public function compress(): void {
		/** @phpstan-var string|false $coords */
		$coords = pack("q*", ...$this->coords);
		/** @phpstan-var string|false $biomes */
		$biomes = pack("N*", ...$this->biomes);

		if($coords === false || $biomes === false) {
			throw new RuntimeException("Error whilst compressing");
		}

		$this->compressedCoords = $coords;
		$this->compressedBiomes = $biomes;

		$this->coords = [];
		$this->biomes = [];
	}

	/**
	 * @internal
	 */
	public function decompress(): void {
		/** @phpstan-var int[]|false $coords */
		$coords = unpack("q*", $this->compressedCoords);
		/** @phpstan-var int[]|false $coords */
		$biomes = unpack("N*", $this->compressedBiomes);

		if($coords === false || $biomes === false) {
			throw new RuntimeException("Error whilst decompressing");
		}

		$this->coords = array_values($coords);
		$this->biomes = array_values($biomes);

		unset($this->compressedCoords);
		unset($this->compressedBiomes);
	}

	/**
	 * @internal
	 */
	public function nbtSerialize(CompoundTag $nbt): void {
		$nbt->setTag("Biomes", (new CompoundTag())
			->setByteArray("Coords", $this->compressedCoords)
			->setByteArray("Biomes", $this->compressedBiomes)
		);
	}

	/**
	 * @internal
	 */
	public function nbtDeserialize(CompoundTag $nbt): void {
		$biomeNbt = $nbt->getCompoundTag("Biomes");
		if($biomeNbt === null) {
			return;
		}

		$this->compressedCoords = $biomeNbt->getByteArray("Coords");
		$this->compressedBiomes = $biomeNbt->getByteArray("Biomes");
	}
}