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
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\session\SessionManager;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use function max;
use function min;

/** @deprecated */
class Replacement {
	use SingletonTrait;

	public function directReplace(Player $player, Vector3 $pos1, Vector3 $pos2, string $blocks, string $replace): UpdateResult {
		$timer = new Timer();

		$mask = new StringToBlockDecoder($blocks, $player->getInventory()->getItemInHand(), false);
		$stringToBlockDecoder = new StringToBlockDecoder($replace, $player->getInventory()->getItemInHand());

		if(!$mask->isValid(false)) { // Nothing to replace
			return UpdateResult::success(0, $timer->time());
		}
		if(!$stringToBlockDecoder->isValid()) {
			return UpdateResult::error("Could not read blocks from $blocks");
		}

		$minX = (int)min($pos1->getX(), $pos2->getX());
		$maxX = (int)max($pos1->getX(), $pos2->getX());
		$minZ = (int)min($pos1->getZ(), $pos2->getZ());
		$maxZ = (int)max($pos1->getZ(), $pos2->getZ());

		$minY = (int)max(min($pos1->getY(), $pos2->getY(), World::Y_MAX - 1), 0);
		$maxY = (int)min(max($pos1->getY(), $pos2->getY(), 0), World::Y_MAX - 1);

		$fillSession = new MaskedFillSession($player->getWorld(), false, true, $mask);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($fillSession->getChanges(), $player->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}
}