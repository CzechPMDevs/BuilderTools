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

namespace czechpmdevs\buildertools\shape;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;

interface Shape {
	/**
	 * Fills the shape
	 */
	public function fill(BlockIdentifierList $blockGenerator, bool $saveReverseData): self;

	/**
	 * Fills all the outer sides of shape
	 */
	public function outline(BlockIdentifierList $blockGenerator, bool $saveReverseData): self;

	/**
	 * Fills all the walls of shape
	 */
	public function walls(BlockIdentifierList $blockGenerator, bool $saveReverseData): self;

	/**
	 * Reads blocks inside the shape
	 */
	public function read(BlockArray $blockArray, bool $unloadReadData = true): self;
}