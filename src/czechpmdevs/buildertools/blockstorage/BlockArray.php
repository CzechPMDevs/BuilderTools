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

use pocketmine\math\Vector3;
use pocketmine\world\World;
use function count;
use function in_array;

final class BlockArray {
	/** @var int[] */
	public array $blocks = [];
	/** @var int[] */
	public array $coords = [];

	/**
	 * Fields to avoid allocating memory every time when writing or reading
	 * block from the array
	 */
	protected int $lastHash;

	public function __construct(
		protected bool $detectDuplicates = false
	) {}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlock(Vector3 $vector3, int $fullBlockId): BlockArray {
		return $this->addBlockAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ(), $fullBlockId);
	}

	/**
	 * Adds block to the block array
	 *
	 * @return $this
	 */
	public function addBlockAt(int $x, int $y, int $z, int $fullBlockId): BlockArray {
		$this->lastHash = World::blockHash($x, $y, $z);

		if($this->detectDuplicates && in_array($this->lastHash, $this->coords, true)) {
			return $this;
		}

		$this->coords[] = $this->lastHash;
		$this->blocks[] = $fullBlockId;

		return $this;
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
	 * @param int[] $blocks
	 */
	public function setBlockArray(array $blocks): void {
		$this->blocks = $blocks;
	}

	/**
	 * @return int[]
	 */
	public function getBlockArray(): array {
		return $this->blocks;
	}

	/**
	 * @param int[] $coords
	 */
	public function setCoordsArray(array $coords): void {
		$this->coords = $coords;
	}

	/**
	 * @return int[]
	 */
	public function getCoordsArray(): array {
		return $this->coords;
	}
}