<?php

declare(strict_types=1);

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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;

class WandCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/wand", "Switch wand tool", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(BuilderTools::getConfiguration()->getBoolProperty("wand-axe.enabled")) {
			$item = VanillaItems::WOODEN_AXE();
			$item->setCustomName(BuilderTools::getConfiguration()->getStringProperty("wand-axe.name"));
			$item->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
			$item->getNamedTag()->setByte("buildertools", 1);

			$sender->getInventory()->addItem($item);
			$sender->sendMessage(BuilderTools::getPrefix() . "§aWand axe added to your inventory!");
			return;
		}

		Selectors::switchWandSelector($sender);
		$switch = Selectors::isWandSelector($sender) ? "ON" : "OFF";
		$sender->sendMessage(BuilderTools::getPrefix() . "§aWand tool turned $switch!");
	}
}