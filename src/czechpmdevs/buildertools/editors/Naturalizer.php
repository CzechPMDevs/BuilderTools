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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\Math;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function microtime;

class Naturalizer {
	use SingletonTrait;

	public function naturalize(Vector3 $pos1, Vector3 $pos2, Player $player): EditorResult {
		$startTime = microtime(true);

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$state = 0;
				for($y = 255; $y >= 0; --$y) {
					$fillSession->getBlockIdAt($x, $y, $z, $id);
					if($id == 0) {
						$state = 0;
					} elseif($state == 0) {
						$state = 1;
						$fillSession->setBlockAt($x, $y, $z, 2, 0); // Grass
					} elseif($state < 5) { // 1 - 3
						if($state == 3) {
							$state += 2;
						} else {
							$state++;
						}
						$fillSession->setBlockAt($x, $y, $z, 3, 0);
					} else {
						$fillSession->setBlockAt($x, $y, $z, 1, 0);
					}
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->save();
		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}
}