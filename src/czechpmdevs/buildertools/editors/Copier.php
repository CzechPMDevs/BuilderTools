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

use czechpmdevs\buildertools\blockstorage\Clipboard;
use czechpmdevs\buildertools\blockstorage\identifiers\SingleBlockIdentifier;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\math\Transform;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function microtime;

/** @deprecated */
class Copier {
	use SingletonTrait;

	public const DIRECTION_PLAYER = 0;
	public const DIRECTION_UP = 1;
	public const DIRECTION_DOWN = 2;

	public function copy(Vector3 $pos1, Vector3 $pos2, Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = (new Clipboard())->setRelativePosition($player->getPosition()->subtract(0.5, 0, 0.5)->floor());

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$clipboard->addBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		$clipboard->unload();
		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);

		return UpdateResult::success(Math::selectionSize($pos1, $pos2), microtime(true) - $startTime);
	}

	public function cut(Vector3 $pos1, Vector3 $pos2, Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = (new Clipboard())->setRelativePosition($player->getPosition()->subtract(0.5, 0, 0.5)->floor());

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$clipboard->addBlockAt($x, $y, $z, $fullBlockId);

					$fillSession->setBlockAt($x, $y, $z, 0);
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$clipboard->unload();
		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);

		$changes = $fillSession->getChanges();
		$changes->unload();

		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function merge(Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			return UpdateResult::error("Clipboard is empty");
		}

		$clipboard->setWorld($player->getWorld());
		$clipboard->load();

		/** @phpstan-var Vector3 $relativePosition */
		$relativePosition = $clipboard->getRelativePosition();

		$fillSession = new MaskedFillSession($player->getWorld(), true, true, SingleBlockIdentifier::airIdentifier());

		$motion = $player->getPosition()->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		while($clipboard->hasNext()) {
			$clipboard->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);
		}

		$clipboard->unload();

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->unload();
		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function paste(Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			return UpdateResult::error("Clipboard is empty");
		}

		$clipboard->setWorld($player->getWorld());
		$clipboard->load();

		/** @phpstan-var Vector3 $relativePosition */
		$relativePosition = $clipboard->getRelativePosition();

		$fillSession = new FillSession($player->getWorld(), true, true);

		$motion = $player->getPosition()->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		while($clipboard->hasNext()) {
			$clipboard->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);
		}

		$clipboard->unload();

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->unload();

		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function rotate(Player $player, int $axis, int $rotation): void {
		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			$player->sendMessage(BuilderTools::getPrefix() . "§cYour clipboard is empty");
			return;
		}

		$transform = new Transform($clipboard);
		if($axis === Axis::Y) {
			$transform->rotateY($rotation);
		} elseif($axis === Axis::X) {
			$transform->rotateX($rotation);
		} else {
			$transform->rotateZ($rotation);
		}

		$transform->close();

		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);
	}

	public function flip(Player $player, int $axis): void {
		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			$player->sendMessage(BuilderTools::getPrefix() . "§cYour clipboard is empty");
			return;
		}

		$transform = new Transform($clipboard);
		if($axis === Axis::X) {
			$transform->flipX();
		} elseif($axis === Axis::Y) {
			$transform->flipY();
		} else {
			$transform->flipZ();
		}

		$transform->close();

		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);
	}
}
