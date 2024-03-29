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

namespace czechpmdevs\buildertools\commands\region;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\commands\BuilderToolsCommand;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use RuntimeException;
use function is_numeric;
use function strtolower;

class StackCommand extends BuilderToolsCommand {
	public function __construct() {
		parent::__construct("/stack", "Stack copied area", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;

		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(!isset($args[0])) {
			$sender->sendMessage("§cUsage: §7//stack <count> [side|up|down]");
			return;
		}

		if(!is_numeric($args[0])) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cType number!");
			return;
		}

		$count = (int)$args[0];

		$direction = null;
		if(isset($args[1])) {
			switch(strtolower($args[1])):
				case "up":
					$direction = Facing::UP;
					break;
				case "down":
					$direction = Facing::DOWN;
			endswitch;
		}

		if($direction === null) {
			$direction = Math::getPlayerDirection($sender);
		}

		try {
			$result = SessionManager::getInstance()->getSession($sender)->getSelectionHolder()->stack($count, $direction);
		} catch(RuntimeException $exception) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§c{$exception->getMessage()}");
			return;
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "Section stacked $count times, {$result->getBlocksChanged()} blocks changed (Took {$result->getProcessTime()})");
	}
}