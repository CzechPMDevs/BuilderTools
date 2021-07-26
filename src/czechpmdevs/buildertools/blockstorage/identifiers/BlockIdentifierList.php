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

namespace czechpmdevs\buildertools\blockstorage\identifiers;

interface BlockIdentifierList {

	/**
	 * Function which outputs next random block
	 */
	public function nextBlock(?int &$id, ?int &$meta): void;

	/**
	 * Function which returns if the block
	 * in the list
	 */
	public function containsBlock(int $blockHash): bool;

	/**
	 * Returns if the id is on the list
	 */
	public function containsBlockId(int $id): bool;
}