<?php

/** @noinspection PhpUnused */

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
use function is_null;

final class BlockArraySizeData {

	private BlockArray $blockArray;

	public ?int $maxX = null, $maxY = null, $maxZ = null;

	public ?int $minX = null, $minY = null, $minZ = null;

	public function __construct(BlockArray $blockArray) {
		$this->blockArray = $blockArray;
		$this->calculateSizeData();
	}

	private function calculateSizeData(): void {
		while ($this->blockArray->hasNext()) {
			$this->blockArray->readNext($x, $y, $z, $id, $meta);
			if(is_null($this->maxX) || $this->maxX < $x) {
				$this->maxX = $x;
			}
			if(is_null($this->maxY) || $this->maxY < $y) {
				$this->maxY = $y;
			}
			if(is_null($this->maxZ) || $this->maxZ < $z) {
				$this->maxZ = $z;
			}

			if(is_null($this->minX) || $this->minX > $x) {
				$this->minX = $x;
			}
			if(is_null($this->minY) || $this->minY > $y) {
				$this->minY = $y;
			}
			if(is_null($this->minZ) || $this->minZ > $z) {
				$this->minZ = $z;
			}
		}

		$this->blockArray->offset = 0;
	}

	/**
	 * Recalculates dimensions of the BlockArray
	 */
	public function recalculate(): void {
		$this->calculateSizeData();
	}

	public function getMinimum(): Vector3 {
		/** @phpstan-ignore-next-line */
		return new Vector3($this->minX, $this->minY, $this->minZ); // Ignored - we provide non-null values
	}

	public function getMaximum(): Vector3 {
		/** @phpstan-ignore-next-line */
		return new Vector3($this->maxX, $this->maxY, $this->maxZ); // Ignored - we provide non-null values
	}

}