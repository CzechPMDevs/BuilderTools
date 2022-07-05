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

namespace czechpmdevs\buildertools\commands\history;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\commands\BuilderToolsCommand;
use czechpmdevs\buildertools\session\SessionManager;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RedoCommand extends BuilderToolsCommand {
	public function __construct() {
		parent::__construct("/redo", "Redo last BuilderTools actions", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}
	
		$redoAction = SessionManager::getInstance()->getSession($sender)->getReverseDataHolder()->nextRedoAction();
		if($redoAction === null) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cThere are not any actions to redo.");
			return;
		}

		$timer = new Timer();
		
		$undoAction = $redoAction->insert(); // TODO - This should not be done in command class
		SessionManager::getInstance()->getSession($sender)->getReverseDataHolder()->saveUndo($undoAction);
		
		$sender->sendMessage(BuilderTools::getPrefix() . "§aUndo was cancelled, {$undoAction->getSize()} blocks changed (Took {$timer->time()} seconds)!");
	}
}
