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
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\world\Position;
use RuntimeException;
use function str_replace;
use function strtolower;

abstract class BuilderToolsCommand extends Command implements PluginOwned {

	public function __construct(string $name, string $description = "", string $usageMessage = null, $aliases = []) {
		$this->setPermission($this->getPerms($name));
		parent::__construct($name, $description, $usageMessage, $aliases);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		$permission = $this->getPermission();
		if($permission === null) {
			throw new RuntimeException("Command " . __CLASS__ . " is registered wrong.");
		}

		if(!$sender->hasPermission($permission)) {
			$sender->sendMessage((string)$this->getPermissionMessage());
		}
	}

	protected function readPositions(Player $sender, ?Position &$firstPos = null, ?Position &$secondPos = null): bool {
		if(!Selectors::isSelected(1, $sender)) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cFirst you need to select the first position.");
			return false;
		}
		if(!Selectors::isSelected(2, $sender)) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cFirst you need to select the second position.");
			return false;
		}

		$firstPos = Selectors::getPosition($sender, 1);
		$secondPos = Selectors::getPosition($sender, 2);

		if($firstPos->getWorld()->getId() != $secondPos->getWorld()->getId()) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cPositions must be in same level");
			return false;
		}

		return true;
	}

	private function getPerms(string $name): string {
		return "buildertools.command." . str_replace("/", "", strtolower($name));
	}

	/** @noinspection PhpUnused */
	public function getOwningPlugin(): Plugin {
		return BuilderTools::getInstance();
	}
}