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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;
use RuntimeException;

class SecondPositionCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/pos2", "Select second position", null, ["/2"]);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;

		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		$selection = SessionManager::getInstance()->getSession($sender)->getSelectionHolder();
		try {
			$selection->handleWandAxeBlockClick($position = Position::fromObject($sender->getPosition()->floor(), $sender->getWorld()));
		} catch(RuntimeException $exception) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§c{$exception->getMessage()}");
			return;
		}

		try {
			$size = " ({$selection->size()})";
		} catch(RuntimeException) {
			$size = "";
		}
		$sender->sendMessage(BuilderTools::getPrefix() . "§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}$size");
	}
}