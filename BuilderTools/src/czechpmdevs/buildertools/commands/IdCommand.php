<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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
use pocketmine\Player;

/**
 * Class IdCommand
 * @package buildertools\commands
 */
class IdCommand extends BuilderToolsCommand {

    /**
     * IdCommand constructor.
     */
    public function __construct() {
        parent::__construct("/id", "Send id of item in your hands", null, []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        $sender->sendMessage(BuilderTools::getPrefix()."§aID: §9{$sender->getInventory()->getItemInHand()->getId()}:{$sender->getInventory()->getItemInHand()->getDamage()}");
    }
}