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
		if($this->blockArray->size() == 0) {
			return;
		}

		$this->blockArray->readNext($x, $y, $z, $id, $meta);

		$minX = $maxX = $x;
		$minY = $maxY = $y;
		$minZ = $maxZ = $z;

		if($this->blockArray->size() % 2 == 0) {
			$this->blockArray->offset = 0;
		}

		while($this->blockArray->hasNext()) {
			$this->blockArray->readNext($x1, $y1, $z1, $id, $meta);
			if(!$this->blockArray->hasNext()) {
				if($minX > $x1) {
					$minX = $x1;
				} else if($maxX < $x1) {
					$maxX = $x1;
				}
				if($minY > $y1) {
					$minY = $y1;
				} else if($maxY < $y1) {
					$maxY = $y1;
				}
				if($minZ > $z1) {
					$minZ = $z1;
				} else if($maxZ < $z1) {
					$maxZ = $z1;
				}
				break;
			}

			$this->blockArray->readNext($x2, $y2, $z2, $id, $meta);
			if($x1 > $x2) {
				if($minX > $x2) {
					$minX = $x2;
				}
				if($maxX < $x1) {
					$maxX = $x1;
				}
			}
			if($y1 > $y2) {
				if($minY > $y2) {
					$minY = $y2;
				}
				if($maxY < $y1) {
					$maxY = $y1;
				}
			}
			if($z1 > $z2) {
				if($minZ > $z2) {
					$minZ = $z2;
				}
				if($maxZ < $z1) {
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

		$this->blockArray->offset = 0;
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