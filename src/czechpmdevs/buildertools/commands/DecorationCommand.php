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
use czechpmdevs\buildertools\editors\Decorator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;
use function is_numeric;
use function str_replace;

class DecorationCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/decoration", "Decoration commands", null, ["/d"]);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}
		if(count($args) <= 2) {
			$sender->sendMessage("§cUsage: §7//d <decoration: id1:dmg1,id2,...> <radius> [percentage: 90%]");
			return;
		}

		if(!is_numeric($args[1])) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cUse integer for radius!");
			return;
		}

		$percentage = 90;
		if(isset($args[2]) && is_numeric(str_replace("%", "", $args[2]))) {
			$percentage = (int)($args[2]);
		}

		$result = Decorator::getInstance()->addDecoration($sender->getPosition(), $args[0], (int)($args[1]), $percentage, $sender);
		if(!$result->successful()) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cAn error occurred whilst adding decoration: {$result->getErrorMessage()}");
			return;
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "§aDecoration placed!");
	}
}