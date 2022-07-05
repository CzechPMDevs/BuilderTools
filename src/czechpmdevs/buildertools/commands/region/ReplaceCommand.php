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

use czechpmdevs\buildertools\blockstorage\identifiers\MergedBlockIdentifier;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\commands\BuilderToolsCommand;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use RuntimeException;

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

		if(($fromBlockIds = $this->createBlockDecoder($sender, $args[0])) === null) {
			return;
		}

		if(($toBlockIds = $this->createBlockDecoder($sender, $args[1])) === null) {
			return;
		}

		try {
			$session = SessionManager::getInstance()->getSession($sender);
			$result = $session->getSelectionHolder()->fill($toBlockIds, $session->getMask() === null ? $fromBlockIds : new MergedBlockIdentifier($fromBlockIds, $session->getMask()));
		} catch(RuntimeException $exception) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§c{$exception->getMessage()}");
			return;
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "§aReplaced {$result->getBlocksChanged()} blocks (Took {$result->getProcessTime()} seconds)!");
	}
}