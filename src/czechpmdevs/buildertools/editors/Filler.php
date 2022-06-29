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
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\session\SessionManager;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

/** @deprecated */
class Filler {
	use SingletonTrait;

	public function directFill(Player $player, Vector3 $pos1, Vector3 $pos2, BlockIdentifierList $blockGenerator, bool $hollow = false): UpdateResult {
		$timer = new Timer();

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		if($hollow) {
			for($x = $minX; $x <= $maxX; ++$x) {
				for($z = $minZ; $z <= $maxZ; ++$z) {
					for($y = $minY; $y <= $maxY; ++$y) {
						if(($x !== $minX && $x !== $maxX) && ($y !== $minY && $y !== $maxY) && ($z !== $minZ && $z !== $maxZ)) {
							continue;
						}

						$blockGenerator->nextBlock($fullBlockId);
						$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
					}
				}
			}
		} else {
			for($x = $minX; $x <= $maxX; ++$x) {
				for($z = $minZ; $z <= $maxZ; ++$z) {
					for($y = $minY; $y <= $maxY; ++$y) {
						$blockGenerator->nextBlock($fullBlockId);
						$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
					}
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($fillSession->getChanges(), $player->getWorld()));
		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function directWalls(Player $player, Vector3 $pos1, Vector3 $pos2, BlockIdentifierList $blocks): UpdateResult {
		$timer = new Timer();

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					if($x === $minX || $x === $maxX || $z === $minZ || $z === $maxZ) {
						$blocks->nextBlock($fullBlockId);
						$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
					}
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($fillSession->getChanges(), $player->getWorld()));
		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}
}