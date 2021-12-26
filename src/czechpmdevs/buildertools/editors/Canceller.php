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

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function microtime;

/** @deprecated */
class Canceller {
	use SingletonTrait;

	public function addStep(Player $player, BlockArray $blocks): void {
		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo($blocks);
	}

	public function undo(Player $player): UpdateResult {
		$undoAction = SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->nextUndoAction();
		if($undoAction === null) {
			return UpdateResult::error("There are not any actions to undo");
		}

		if($undoAction->getWorld() === null) {
			return UpdateResult::error("Could not find world to process changes on");
		}

		$startTime = microtime(true);
		$fillSession = new FillSession($undoAction->getWorld(), true, true);

		$undoAction->load();
		while($undoAction->hasNext()) {
			$undoAction->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$updates = $fillSession->getChanges();
		$updates->save();

		Canceller::getInstance()->addRedo($player, $updates);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function addRedo(Player $player, BlockArray $blocks): void {
		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveRedo($blocks);
	}

	public function redo(Player $player): UpdateResult {
		$redoAction = SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->nextRedoAction();
		if($redoAction === null) {
			return UpdateResult::error("There are not any actions to undo");
		}

		if($redoAction->getWorld() === null) {
			return UpdateResult::error("Could not find world to process changes on");
		}

		$startTime = microtime(true);
		$fillSession = new FillSession($redoAction->getWorld(), true, true);

		$redoAction->load();
		while($redoAction->hasNext()) {
			$redoAction->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$updates = $fillSession->getChanges();
		$updates->save();

		Canceller::getInstance()->addStep($player, $updates);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}
}