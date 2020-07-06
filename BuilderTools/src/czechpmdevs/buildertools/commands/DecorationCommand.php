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
use czechpmdevs\buildertools\editors\Decorator;
use czechpmdevs\buildertools\editors\Editor;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class DecorationCommand
 * @package czechpmdevs\buildertools\commands
 */
class DecorationCommand extends BuilderToolsCommand {

    public function __construct() {
        parent::__construct("/decoration", "Decoration commands", null, ["/d"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        if(count($args) <= 2) {
            $sender->sendMessage("§cUsage: §7//d <decoration: id1:dmg1,id2,...> <radius> <percentage: 30%> <radius: cube> ");
            return;
        }

        $percentage = 30;

        if(isset($args[2]) && is_numeric($args[2])) {
            $percentage = intval($args[2]);
        }

        /** @var Decorator $decorator */
        $decorator = BuilderTools::getEditor(Editor::DECORATOR);

        $decorator->addDecoration($sender, $args[0], (int)round($args[1]), $percentage);

        $sender->sendMessage(BuilderTools::getPrefix()."§aDecoration placed!");
    }
}