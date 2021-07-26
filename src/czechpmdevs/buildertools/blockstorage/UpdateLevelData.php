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

use pocketmine\world\ChunkManager;

interface UpdateLevelData {

	/**
	 * @return bool Returns if it is possible read next blocks
	 */
	public function hasNext(): bool;

	/**
	 * Reads next block from the array
	 */
	public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$id, ?int &$meta): void;

	/**
	 * Should not be null when used in filler
	 */
	public function getWorld(): ?ChunkManager;
}