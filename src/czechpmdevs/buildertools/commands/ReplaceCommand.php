<?php

declare(strict_types=1);

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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Replacement;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ReplaceCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/replace", "Replace selected blocks", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}
		if(!isset($args[0]) || !isset($args[1])) {
			$sender->sendMessage("§cUsage: §7//replace <BlocksToReplace - id1:meta1,id2:meta2,...> <Blocks - id1:meta1,id2:meta2,...>");
			return;
		}

		if(!$this->readPositions($sender, $firstPos, $secondPos)) {
			return;
		}

		$result = Replacement::getInstance()->directReplace($sender, $firstPos, $secondPos, $args[0], $args[1]);
		if(!$result->successful()) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cError whilst processing the command: {$result->getErrorMessage()}");
			return;
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "§aReplaced {$result->getBlocksChanged()} blocks (Took {$result->getProcessTime()} seconds)!");
	}
}