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
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use function microtime;
use function mt_rand;

class Decorator {
	use SingletonTrait;

	public function addDecoration(Position $center, string $blocks, int $radius, int $percentage, Player $player): EditorResult {
		$startTime = microtime(true);

		$fillSession = new FillSession($center->getWorld(), false, true);

		$stringToBlockDecoder = new StringToBlockDecoder($blocks);

		$minX = $center->getFloorX() - $radius;
		$maxX = $center->getFloorX() + $radius;
		$minZ = $center->getFloorZ() - $radius;
		$maxZ = $center->getFloorZ() + $radius;

		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);

		for($x = $minX; $x <= $maxX; ++$x) {
			/** @var int $x */
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if(mt_rand(1, 100) > $percentage) {
					continue;
				}

				if(!$fillSession->getHighestBlockAt($x, $z, $y)) {
					continue;
				}

				$stringToBlockDecoder->nextBlock($id, $meta);
				$fillSession->setBlockAt($x, $y, $z, $id, $meta);
			}
		}

		$fillSession->reloadChunks($center->getWorld());

		$changes = $fillSession->getChanges();
		$changes->save();
		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}
}
