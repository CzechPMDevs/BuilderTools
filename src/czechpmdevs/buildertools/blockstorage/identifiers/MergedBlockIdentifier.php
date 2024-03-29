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

namespace czechpmdevs\buildertools\blockstorage\identifiers;

use pocketmine\utils\AssumptionFailedError;

class MergedBlockIdentifier implements BlockIdentifierList {

	public function __construct(
		public BlockIdentifierList $blockIdentifierList,
		public BlockIdentifierList $filter
	) {}

	public function nextBlock(?int &$fullBlockId): void {
		throw new AssumptionFailedError("nextBlock does not work with MergedBlockIdentifier");
	}

	public function containsBlock(int $fullBlockId): bool {
		return $this->blockIdentifierList->containsBlock($fullBlockId) && $this->filter->containsBlock($fullBlockId);
	}

	public function containsBlockId(int $id): bool {
		return $this->blockIdentifierList->containsBlockId($id) && $this->filter->containsBlockId($id);
	}
}