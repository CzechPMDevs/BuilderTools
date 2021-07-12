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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;
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

        if(BuilderTools::getConfiguration()["items"]["wand-axe"]["enabled"]) {
            $item = VanillaItems::WOODEN_AXE();
            $item->setCustomName(BuilderTools::getConfiguration()["items"]["wand-axe"]["name"]);
			$nbt = $item->getNamedTag();
			$nbt->setByte("buildertools", 1);
			$item->setNamedTag($nbt);
			/** @phpstan-ignore-next-line */
            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
            $sender->getInventory()->addItem($item);
            $sender->sendMessage(BuilderTools::getPrefix() . "§aWand axe added to your inventory!");
            return;
        }

        Selectors::switchWandSelector($sender);
        $switch = Selectors::isWandSelector($sender) ? "ON" : "OFF";
        $sender->sendMessage(BuilderTools::getPrefix()."§aWand tool turned $switch!");
    }
}