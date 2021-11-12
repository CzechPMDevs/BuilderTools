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
use pocketmine\command\CommandSender;
use function asort;
use function count;
use function is_numeric;
use function is_string;

class HelpCommand extends BuilderToolsCommand {

	public const COMMANDS_PER_PAGE = 5;

	/** @var string[] */
	public static array $pages = [];

	public function __construct() {
		parent::__construct("/help", "Displays BuilderTools commands", null, ["/?", "buildertools", "/commands"]);
	}

	public static function buildPages(): void {
		$commandsPerList = HelpCommand::COMMANDS_PER_PAGE;

		$count = (int)(count(BuilderTools::getAllCommands()) / $commandsPerList);
		$list = 1;
		$command = 1;
		$text = "";
		$all = 0;

		//sort
		$commands = [];

		foreach(BuilderTools::getAllCommands() as $i => $cmd) {
			$commands[$i] = $cmd->getName();
		}

		asort($commands);

		foreach($commands as $index => $name) {
			++$all;
			if($command == 1) {
				$text = "§2--- Showing help page $list of $count ---";
			}

			$description = BuilderTools::getAllCommands()[$index]->getDescription();
			$text .= "\n§2/$name: §f" . (is_string($description) ? $description : $description->getText());
			if($command == HelpCommand::COMMANDS_PER_PAGE || (count(BuilderTools::getAllCommands()) == $all)) {
				$command = 1;
				HelpCommand::$pages[$list] = $text;
				$list++;
			} else {
				$command++;
			}
		}
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		$page = 1;
		if(isset($args[0]) && is_numeric($args[0]) && (int)$args[0] <= ((int)(count(BuilderTools::getAllCommands()) / HelpCommand::COMMANDS_PER_PAGE))) {
			$page = (int)$args[0];
		}

		$sender->sendMessage(HelpCommand::$pages[$page]);
	}
}
