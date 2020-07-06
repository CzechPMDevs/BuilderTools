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
use pocketmine\level\generator\object\BirchTree;
use pocketmine\level\generator\object\JungleTree;
use pocketmine\level\generator\object\OakTree;
use pocketmine\level\generator\object\SpruceTree;
use pocketmine\Player;
use pocketmine\utils\Random;

/**
 * Class TreeCommand
 * @package buildertools\commands
 */
class TreeCommand extends BuilderToolsCommand {

    /**
     * TreeCommand constructor.
     */
    public function __construct() {
        parent::__construct("/tree", "Place tree object", null, []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }

        if(!isset($args[0])) {
            $sender->sendMessage("§cUsage: §7/tree <list|treeType>");
            return;
        }

        if(strtolower($args[0]) == "list") {
            $sender->sendMessage(BuilderTools::getPrefix()."§aTree list: Birch, Oak, Jungle, Spruce");
            return;
        }

        $object = null;

        switch (strtolower($args[0])) {
            case "oak":
                $object = new OakTree;
                break;
            case "birch":
                $object = new BirchTree;
                break;
            case "jungle":
                $object = new JungleTree;
                break;
            case "spruce":
                $object = new SpruceTree;
                break;
        }

        if($object === null) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cObject {$args[0]} does not found!");
            return;
        }

        $object->placeObject($sender->getLevel(), (int)$sender->getX(), (int)$sender->getY(), (int)$sender->getZ(), new Random($sender->getLevel()->getSeed()));
        $sender->sendMessage(BuilderTools::getPrefix()."§aObject {$args[0]} placed!");
    }
}
