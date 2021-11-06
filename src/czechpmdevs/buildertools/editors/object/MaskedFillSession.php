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

namespace czechpmdevs\buildertools\editors\object;

use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use pocketmine\world\ChunkManager;

class MaskedFillSession extends FillSession {

	protected ?BlockIdentifierList $mask;

	public function __construct(ChunkManager $world, bool $calculateDimensions = true, bool $saveChanges = true, ?BlockIdentifierList $mask = null) {
		parent::__construct($world, $calculateDimensions, $saveChanges);

		$this->mask = $mask;
	}

	/**
	 * @param int $y 0-255
	 */
	public function setBlockAt(int $x, int $y, int $z, int $id, int $meta): void {
		if(!$this->moveTo($x, $y, $z)) {
			return;
		}

		// TODO
		if($this->mask !== null && (
			!$this->mask->containsBlock(
			/** @phpstan-ignore-next-line */
				$this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf)
			)
			) && (
			!$this->mask->containsBlockId(
			/** @phpstan-ignore-next-line */
				$this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf) >> 4
			)
			)
		) {
			return;
		}

		$this->saveChanges($x, $y, $z);

		/** @phpstan-ignore-next-line */
		$this->explorer->currentSubChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, $id << 4 | $meta);
		$this->blocksChanged++;
	}

	/**
	 * @param int $y 0-255
	 */
	public function setBlockIdAt(int $x, int $y, int $z, int $id): void {
		if(!$this->moveTo($x, $y, $z)) {
			return;
		}

		if($this->mask !== null && !$this->mask->containsBlock(
			/** @phpstan-ignore-next-line */
				$this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf)
			)) {
			return;
		}

		$this->saveChanges($x, $y, $z);

		/** @phpstan-ignore-next-line */
		$this->explorer->currentSubChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, $id);
		$this->blocksChanged++;
	}
}