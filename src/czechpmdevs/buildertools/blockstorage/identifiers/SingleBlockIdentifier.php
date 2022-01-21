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

use pocketmine\block\Block;

class SingleBlockIdentifier implements BlockIdentifierList {

	protected int $id;
	protected int $meta;

	public function __construct(int $id, ?int $meta = null) {
		$this->id = $id;
		if($meta !== null) {
			$this->meta = $meta;
		}
	}

	public function nextBlock(?int &$fullBlockId): void {
		$fullBlockId = $this->id << Block::INTERNAL_METADATA_BITS | $this->meta;
	}

	public function containsBlock(int $fullBlockId): bool {
		return isset($this->meta) ? $fullBlockId === ($this->id << Block::INTERNAL_METADATA_BITS | $this->meta) : $fullBlockId >> Block::INTERNAL_METADATA_BITS === $this->id;
	}

	public function containsBlockId(int $id): bool {
		return $this->id === $id;
	}

	public static function airIdentifier(): SingleBlockIdentifier {
		return new SingleBlockIdentifier(0, 0);
	}
}