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
use czechpmdevs\buildertools\session\selection\CuboidSelection;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\format\SubChunk;
use pocketmine\world\Position;
use pocketmine\world\World;

class ChunkCommand extends BuilderToolsCommand {
	public function __construct() {
		parent::__construct("/chunk", "Selects the whole chunk as selection");
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		$selection = SessionManager::getInstance()->getSession($sender)->getSelectionHolder();
		if(!$selection instanceof CuboidSelection) {
			$sender->sendMessage(BuilderTools::getPrefix() . "§cIt is not possible to select chunk with current selection type. Use §l//sel cuboid§r§c and try executing the command again.");
			return;
		}

		$realChunkX = $sender->getPosition()->getFloorX() >>  SubChunk::COORD_BIT_SIZE << SubChunk::COORD_BIT_SIZE;
		$realChunkZ = $sender->getPosition()->getFloorZ() >> SubChunk::COORD_BIT_SIZE << SubChunk::COORD_BIT_SIZE;

		$selection->handleWandAxeBlockBreak(new Position($realChunkX, World::Y_MIN, $realChunkZ, $sender->getWorld()));
		$selection->handleWandAxeBlockClick(new Position($realChunkX + 15, World::Y_MAX, $realChunkZ + 15, $selection->getWorld()));

		$sender->sendMessage(BuilderTools::getPrefix() . "Chunk selected ({$selection->size()})");
	}
}