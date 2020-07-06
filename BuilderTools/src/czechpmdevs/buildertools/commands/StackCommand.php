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
use czechpmdevs\buildertools\editors\Copier;
use czechpmdevs\buildertools\editors\Editor;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class StackCommand
 * @package czechpmdevs\buildertools\commands
 */
class StackCommand extends BuilderToolsCommand {

    /**
     * StackCommand constructor.
     */
    public function __construct() {
        parent::__construct("/stack", "Stack copied area", null, []);
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
        if(!isset($args[0])) {
            $sender->sendMessage("§cUsage: §7//stack <count> [side|up|down]");
            return;
        }
        if(!is_numeric($args[0])) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cType number!");
            return;
        }

        $count = (int)$args[0];
        $mode = Copier::DIRECTION_PLAYER;
        if(isset($args[1])) {
            switch (strtoupper($args[1])) {
                case "UP":
                    $mode = Copier::DIRECTION_UP;
                    break;
                case "DOWN":
                    $mode = Copier::DIRECTION_DOWN;
                    break;
            }
        }

        /** @var Copier $copier */
        $copier = BuilderTools::getEditor(Editor::COPIER);
        $copier->stack($sender, $count, $mode);
    }
}