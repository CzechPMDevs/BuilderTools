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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\session\selection\CuboidSelection;
use czechpmdevs\buildertools\session\selection\ExtendSelection;
use czechpmdevs\buildertools\session\selection\PolygonalSelection;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function get_class;
use function strtolower;

class SelectionCommand extends BuilderToolsCommand {
	public function __construct() {
		parent::__construct("/selection", "Change the current selection style to a specific one.", null, ["/sel"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(!isset($args[0])) {
			$sender->sendMessage("§cUsage: §7//sel <cuboid|extend|poly>");
			return;
		}

		$class = match(strtolower($args[0])) {
			"cuboid" => CuboidSelection::class,
			"extend" => ExtendSelection::class,
			"poly" => PolygonalSelection::class,
			default => null
		};

		if($class === null) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cUnknown selection type '$args[0]'.");
			return;
		}

		$session = SessionManager::getInstance()->getSession($sender);
		if(get_class($session->getSelectionHolder()) === $class) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cYou are already using '$args[0]' selection type.");
			return;
		}

		$session->setSelectionHolder(new $class($session));
		$sender->sendMessage(BuilderTools::getPrefix() . "§aSelection type updated to '$args[0]'!");
	}
}