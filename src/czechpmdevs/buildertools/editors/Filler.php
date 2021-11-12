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

use czechpmdevs\buildertools\blockstorage\identifiers\SingleBlockIdentifier;
use czechpmdevs\buildertools\blockstorage\UpdateLevelData;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function microtime;

class Filler {
	use SingletonTrait;

	public function directFill(Player $player, Vector3 $pos1, Vector3 $pos2, string $blockArgs, bool $hollow = false): EditorResult {
		$startTime = microtime(true);

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());
		if(!$stringToBlockDecoder->isValid()) {
			return EditorResult::error("No blocks specified in string {$blockArgs}");
		}

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		if($hollow) {
			for($x = $minX; $x <= $maxX; ++$x) {
				for($z = $minZ; $z <= $maxZ; ++$z) {
					for($y = $minY; $y <= $maxY; ++$y) {
						if(($x != $minX && $x != $maxX) && ($y != $minY && $y != $maxY) && ($z != $minZ && $z != $maxZ)) {
							continue;
						}

						$stringToBlockDecoder->nextBlock($id, $meta);
						$fillSession->setBlockAt($x, $y, $z, $id, $meta);
					}
				}
			}
		} else {
			for($x = $minX; $x <= $maxX; ++$x) {
				for($z = $minZ; $z <= $maxZ; ++$z) {
					for($y = $minY; $y <= $maxY; ++$y) {
						$stringToBlockDecoder->nextBlock($id, $meta);
						$fillSession->setBlockAt($x, $y, $z, $id, $meta);
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

	public function directWalls(Player $player, Vector3 $pos1, Vector3 $pos2, string $blockArgs): EditorResult {
		$startTime = microtime(true);

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					if($x == $minX || $x == $maxX || $z == $minZ || $z == $maxZ) {
						$stringToBlockDecoder->nextBlock($id, $meta);
						$fillSession->setBlockAt($x, $y, $z, $id, $meta);
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

	/**
	 * @deprecated FillSession makes fill direct & faster
	 * @link FillSession
	 */
	public function fill(Player $player, UpdateLevelData $changes, ?Vector3 $relativePosition = null, bool $saveUndo = true, bool $saveRedo = false, bool $airMask = false): EditorResult {
		if($changes->getWorld() === null) {
			return EditorResult::error("Could not find world to process updates on.");
		}

		$startTime = microtime(true);

		if($airMask) {
			$fillSession = new MaskedFillSession($changes->getWorld(), true, true, SingleBlockIdentifier::airIdentifier());
		} else {
			$fillSession = new FillSession($changes->getWorld(), true, $saveUndo || $saveRedo);
		}

		if($relativePosition === null) {
			while($changes->hasNext()) {
				$changes->readNext($x, $y, $z, $id, $meta);
				$fillSession->setBlockAt($x, $y, $z, $id, $meta);
			}
		} else {
			$floorX = $relativePosition->getFloorX();
			$floorY = $relativePosition->getFloorY();
			$floorZ = $relativePosition->getFloorZ();

			while($changes->hasNext()) {
				$changes->readNext($x, $y, $z, $id, $meta);
				$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $id, $meta);
			}
		}

		if($saveUndo || $saveRedo) {
			$updates = $fillSession->getChanges();
			$updates->save();

			if($saveUndo) {
				Canceller::getInstance()->addStep($player, $updates);
			}
			if($saveRedo) {
				Canceller::getInstance()->addRedo($player, $updates);
			}
		}

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}
}