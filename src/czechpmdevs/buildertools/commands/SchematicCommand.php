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
use czechpmdevs\buildertools\schematics\SchematicActionResult;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;
use function implode;
use function in_array;

class SchematicCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/schematic", "Schematics commands", null, ["/schem", "/schematics"]);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}
		if(!isset($args[0]) || !in_array($args[0], ["load", "unload", "list", "paste", "create"], true)) {
			$sender->sendMessage("§cUsage: §7//schem <load|unload|create|list|paste> [filename]");
			return;
		}

		switch($args[0]):
			case "create":
				if(!isset($args[1])) {
					$sender->sendMessage("§cUsage: §7//schem <create> <name>");
					break;
				}

				if(!$this->readPositions($sender, $firstPos, $secondPos)) {
					break;
				}

				$sender->sendMessage(BuilderTools::getPrefix() . "§6Saving schematic in background...");
				SchematicsManager::createSchematic($sender, $firstPos, $secondPos, $args[1], function(SchematicActionResult $result) use ($sender): void {
					if($result->successful()) {
						$sender->sendMessage(BuilderTools::getPrefix() . "§aSchematic saved! (Took {$result->getProcessTime()} seconds)");
						return;
					}

					$sender->sendMessage(BuilderTools::getPrefix() . "§cUnable to create schematic: {$result->getErrorMessage()}");
				});
				break;

			case "load":
				if(!isset($args[1])) {
					$sender->sendMessage("§cUsage: §7//schem load <name>");
					break;
				}

				$sender->sendMessage(BuilderTools::getPrefix() . "§6Loading schematic in background...");
				SchematicsManager::loadSchematic($args[1], function(SchematicActionResult $result) use ($args, $sender): void {
					if($result->successful()) {
						$sender->sendMessage(BuilderTools::getPrefix() . "§aSchematic loaded in {$result->getProcessTime()} seconds, to paste schematic use §e//schem paste $args[1]§a!");
						return;
					}

					$sender->sendMessage(BuilderTools::getPrefix() . "§cError whilst loading schematic: {$result->getErrorMessage()}");
				});
				break;

			case "unload":
				if(!isset($args[1])) {
					$sender->sendMessage("§cUsage: §7//schem unload <name>");
					break;
				}

				if(!SchematicsManager::unloadSchematic($args[1])) {
					$sender->sendMessage(BuilderTools::getPrefix() . "§cSchematic $args[1] is not loaded.");
					break;
				}

				$sender->sendMessage(BuilderTools::getPrefix() . "§aSchematic $args[1] unloaded from memory!");
				break;

			case "paste":
				if(!isset($args[1])) {
					$sender->sendMessage("§cUsage: §7//schem paste <name>");
					break;
				}

				$result = SchematicsManager::pasteSchematic($sender, $args[1]);
				if($result->successful()) {
					$sender->sendMessage(BuilderTools::getPrefix() . "§aSchematic pasted, {$result->getBlocksChanged()} blocks changed (Took {$result->getProcessTime()} seconds)");
					break;
				}

				$sender->sendMessage(BuilderTools::getPrefix() . "§cError whilst pasting schematic: {$result->getErrorMessage()}");
				break;

			case "list":
				$loaded = SchematicsManager::getLoadedSchematics();
				if(count($loaded) == 0) {
					$sender->sendMessage(BuilderTools::getPrefix() . "§cThere aren't any loaded schematics on the server");
					break;
				}
				$sender->sendMessage(BuilderTools::getPrefix() . count($loaded) . " loaded schematics: " . implode(", ", $loaded));
				break;
		endswitch;
	}
}