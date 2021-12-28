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
use czechpmdevs\buildertools\session\selection\CuboidSelection;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use RuntimeException;

class LineCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/line", "Creates a line between two positions");
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(!isset($args[0])) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cUsage: §7//line <id1:meta1,id2:meta2,...>");
			return;
		}

		if(($blockGenerator = $this->createBlockDecoder($sender, $args[0])) === null) {
			return;
		}

		$session = SessionManager::getInstance()->getSession($sender);
		$selection = $session->getSelectionHolder();
		if(!$selection instanceof CuboidSelection) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cIt is not possible to select chunk with current selection type. Use §l//sel cuboid§r§c and try executing the command again.");
			return;
		}

		try {
			$result = $selection->createLineBetweenPositions($blockGenerator, $session->getMask());
		} catch(RuntimeException $exception) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§c{$exception->getMessage()}");
			return;
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "Line has been successfully created, {$result->getBlocksChanged()} blocks changed (Took {$result->getProcessTime()} seconds)!");
	}
}