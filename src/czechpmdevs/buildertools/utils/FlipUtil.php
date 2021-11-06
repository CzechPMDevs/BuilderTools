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

namespace czechpmdevs\buildertools\utils;

use czechpmdevs\buildertools\blockstorage\SelectionData;
use pocketmine\world\World;

class FlipUtil {

	public static function flip(SelectionData $selection, int $axis = Axis::Y_AXIS, int $motion = 0): SelectionData {
		$modifiedSelection = new SelectionData();
		$modifiedSelection->setWorld($selection->getWorld());
		$modifiedSelection->setPlayerPosition($selection->getPlayerPosition());

		$sizeData = $selection->getSizeData();
		if($axis == Axis::X_AXIS) { // y & z const
			while($selection->hasNext()) {
				$selection->readNext($x, $y, $z, $id, $meta);
				FlipHelper::flip($axis, $id, $meta);
				$modifiedSelection->addBlockAt((($sizeData->minX + $sizeData->maxX) - $x) + $motion, $y, $z, $id, $meta);
			}
		} elseif($axis == Axis::Y_AXIS) { // x & z const
			while($selection->hasNext()) {
				$selection->readNext($x, $y, $z, $id, $meta);
				$y = (($sizeData->minY + $sizeData->maxY) - $y) + $motion;
				if($y < World::Y_MIN || $y >= World::Y_MAX) {
					continue;
				}
				FlipHelper::flip($axis, $id, $meta);
				$modifiedSelection->addBlockAt($x, $y, $z, $id, $meta);
			}
		} else {
			while($selection->hasNext()) { // x & y const
				$selection->readNext($x, $y, $z, $id, $meta);
				FlipHelper::flip($axis, $id, $meta);
				$modifiedSelection->addBlockAt($x, $y, (($sizeData->minZ + $sizeData->maxZ) - $z) + $motion, $id, $meta);
			}
		}
		$selection->offset = 0;

		return $modifiedSelection;
	}
}