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
use czechpmdevs\buildertools\BuilderTools;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_values;

class DuplicateBlockCleanHelper {
	public function cleanDuplicateBlocks(BlockArray $blockArray): void {
		if(!(BuilderTools::getConfiguration()->getBoolProperty("remove-duplicate-blocks"))) {
			return;
		}

		// This seems to be the fastest way to remove duplicate blocks. It is even faster
		// than just sorting keys array, or combining those arrays manually. In the future,
		// it would be better to add this to php extension.
		$blocks = array_combine(array_reverse($blockArray->getCoordsArray(), true), array_reverse($blockArray->getBlockArray(), true));

		$blockArray->setCoordsArray(array_keys($blocks));
		$blockArray->setBlockArray(array_values($blocks));
	}
}