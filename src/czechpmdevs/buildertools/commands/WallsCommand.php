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
use czechpmdevs\buildertools\editors\Filler;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class WallsCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/walls", "Makes walls around selection", null, ["/wall"]);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}
		if(!isset($args[0])) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cUsage: §7//walls <id1:meta1,id2:meta2,...>");
			return;
		}

		if(!$this->readPositions($sender, $firstPos, $secondPos)) {
			return;
		}

		$result = Filler::getInstance()->directWalls($sender, $firstPos, $secondPos, $args[0]);
		if(!$result->successful()) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cError while processing the command: {$result->getErrorMessage()}");
			return;
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "Walls made, §a{$result->getBlocksChanged()} changed (Took {$result->getProcessTime()} seconds)");
	}
}