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

namespace czechpmdevs\buildertools\blockstorage\helpers;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use pocketmine\world\World;
use function count;

class BlockArrayIteratorHelper {
	protected int $lastHash;

	public function __construct(
		protected BlockArray $blockArray,
		protected int $offset = 0
	) {}

	/**
	 * Returns if it is possible read next block from the array
	 */
	public function hasNext(): bool {
		return $this->offset < count($this->blockArray->blocks);
	}

	/**
	 * Reads next block in the array
	 */
	public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$fullStateId): void {
		$this->lastHash = $this->blockArray->coords[$this->offset];

		World::getBlockXYZ($this->lastHash, $x, $y, $z);
		$fullStateId = $this->blockArray->blocks[$this->offset++];
	}

	public function resetOffset(): void {
		$this->offset = 0;
	}
}