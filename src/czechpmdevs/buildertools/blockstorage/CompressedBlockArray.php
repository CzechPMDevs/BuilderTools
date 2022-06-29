<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
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

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\AssumptionFailedError;
use function array_values;
use function pack;
use function unpack;
use function var_dump;

class CompressedBlockArray {
	protected string $compressedBlocks;
	protected string $compressedCoords;

	protected int $size;

	public function __construct(BlockArray $blockArray) {
		$this->compressedCoords = pack("q*", ...$blockArray->getCoordsArray());
		$this->compressedBlocks = pack("N*", ...$blockArray->getBlockArray());

		$this->size = $blockArray->size();
	}

	public function getSize(): int {
		return $this->size;
	}

	public function asBlockArray(): BlockArray {
		$blockArray = new BlockArray();

		/** @phpstan-var int[]|false $coords */
		$coords = unpack("q*", $this->compressedCoords);
		/** @phpstan-var int[]|false $blocks */
		$blocks = unpack("N*", $this->compressedBlocks);

		if($coords === false || $blocks === false) {
			throw new AssumptionFailedError("Error whilst decompressing");
		}

		var_dump(pack("q*", ...$coords) === $this->compressedCoords);
		var_dump(pack("N*", ...$blocks) === $this->compressedBlocks);

		$blockArray->setCoordsArray(array_values($coords));
		$blockArray->setBlockArray(array_values($blocks));

		return $blockArray;
	}

	public function nbtSerialize(): CompoundTag {
		$nbt = new CompoundTag();
		$nbt->setByteArray("Coords", $this->compressedCoords);
		$nbt->setByteArray("Blocks", $this->compressedBlocks);

		return $nbt;
	}

	public static function nbtDeserialize(CompoundTag $nbt): self {
		$instance = new self(new BlockArray());
		$instance->compressedCoords = $nbt->getByteArray("Coords");
		$instance->compressedBlocks = $nbt->getByteArray("Blocks");

		return $instance;
	}
}