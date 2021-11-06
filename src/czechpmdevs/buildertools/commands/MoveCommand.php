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
use czechpmdevs\buildertools\editors\Copier;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function count;
use function is_numeric;

class MoveCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/move", "Move selected area", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(count($args) < 3 || !is_numeric($args[0]) || !is_numeric($args[1]) || !is_numeric($args[2])) {
			$sender->sendMessage("§cUsage: §7//move <x> <y> <z>");
			return;
		}

		if(!$this->readPositions($sender, $firstPos, $secondPos)) {
			return;
		}

		$result = Copier::getInstance()->move($firstPos, $secondPos, new Vector3((int)$args[0], (int)$args[1], (int)$args[2]), $sender);

		$sender->sendMessage(BuilderTools::getPrefix() . "§aSelected area has been successfully moved, {$result->getBlocksChanged()} blocks changed (Took {$result->getProcessTime()} seconds)!");
	}
}