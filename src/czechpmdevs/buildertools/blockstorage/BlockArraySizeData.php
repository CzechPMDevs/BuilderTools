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

use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use pocketmine\math\Vector3;

final class BlockArraySizeData {
	private BlockArray $blockArray;

	public int $maxX, $maxY, $maxZ;
	public int $minX, $minY, $minZ;

	public function __construct(BlockArray $blockArray) {
		$this->blockArray = $blockArray;
		$this->calculateSizeData();
	}

	private function calculateSizeData(): void {
		if($this->blockArray->size() === 0) {
			return;
		}

		$iterator = new BlockArrayIteratorHelper($this->blockArray);

		$iterator->readNext($x, $y, $z, $fullStateId);

		$minX = $maxX = $x;
		$minY = $maxY = $y;
		$minZ = $maxZ = $z;

		if($this->blockArray->size() % 2 === 0) {
			$iterator->resetOffset();
		}

		while($iterator->hasNext()) {
			$iterator->readNext($x1, $y1, $z1, $fullStateId);
			if(!$iterator->hasNext()) {
				if($x1 < $minX) {
					$minX = $x1;
				} elseif($x1 > $maxX) {
					$maxX = $x1;
				}
				if($y1 < $minY) {
					$minY = $y1;
				} elseif($y1 > $maxY) {
					$maxY = $y1;
				}
				if($z1 < $minZ) {
					$minZ = $z1;
				} elseif($z1 > $maxZ) {
					$maxZ = $z1;
				}
				break;
			}

			$iterator->readNext($x2, $y2, $z2, $fullStateId);
			if($x1 > $x2) {
				if($x2 < $minX) {
					$minX = $x2;
				}
				if($x1 > $maxX) {
					$maxX = $x1;
				}
			} else {
				if($x1 < $minX) {
					$minX = $x2;
				}
				if($x2 > $maxX) {
					$maxX = $x1;
				}
			}
			if($y1 > $y2) {
				if($y2 < $minY) {
					$minY = $y2;
				}
				if($y1 > $maxY) {
					$maxY = $y1;
				}
			} else {
				if($y1 < $minY) {
					$minY = $y2;
				}
				if($y2 > $maxY) {
					$maxY = $y1;
				}
			}
			if($z1 > $z2) {
				if($z2 < $minZ) {
					$minZ = $z2;
				}
				if($z1 > $maxZ) {
					$maxZ = $z1;
				}
			} else {
				if($z1 < $minZ) {
					$minZ = $z2;
				}
				if($z2 > $maxZ) {
					$maxZ = $z1;
				}
			}
		}

		$this->minX = $minX;
		$this->minY = $minY;
		$this->minZ = $minZ;

		$this->maxX = $maxX;
		$this->maxY = $maxY;
		$this->maxZ = $maxZ;

		$iterator->resetOffset();
	}

	/**
	 * Recalculates dimensions of the BlockArray
	 */
	public function recalculate(): void {
		$this->calculateSizeData();
	}

	public function getMinimum(): Vector3 {
		return new Vector3($this->minX, $this->minY, $this->minZ);
	}

	public function getMaximum(): Vector3 {
		return new Vector3($this->maxX, $this->maxY, $this->maxZ);
	}
}