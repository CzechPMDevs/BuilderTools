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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\session\SessionManager;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use function mt_rand;

/** @deprecated */
class Decorator {
	use SingletonTrait;

	public function addDecoration(Position $center, string $blocks, int $radius, int $percentage, Player $player): UpdateResult {
		$timer = new Timer();

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

				$stringToBlockDecoder->nextBlock($fullStateId);
				$fillSession->setBlockAt($x, $y, $z, $fullStateId);
			}
		}

		$fillSession->reloadChunks($center->getWorld());

		$updates = $fillSession->getChanges();

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($updates, $center->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}
}
