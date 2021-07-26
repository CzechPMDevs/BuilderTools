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
use czechpmdevs\buildertools\editors\Copier;
use czechpmdevs\buildertools\utils\Axis;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function microtime;
use function round;
use function strtolower;

class FlipCommand extends BuilderToolsCommand {

    public function __construct() {
        parent::__construct("/flip", "Flips selected area", null, []);
    }

    /** @noinspection PhpUnused */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }

        if(!isset($args[0])) {
            $sender->sendMessage("§cUsage: §7//flip <axis: x|y|z>");
            return;
        }

        if(strtolower($args[0]) == "x") {
            $axis = Axis::X_AXIS;
        } else if(strtolower($args[0]) == "y") {
            $axis = Axis::Y_AXIS;
        } else if(strtolower($args[0]) == "z") {
            $axis = Axis::Z_AXIS;
        } else {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cUnknown axis '$args[0]'. You can use only 'X', 'Y' and 'Z' axis.");
            return;
        }

        $startTime = microtime(true);

        $copier = Copier::getInstance();
        $copier->flip($sender, $axis);

        $time = round(microtime(true)-$startTime, 3);

        $sender->sendMessage(BuilderTools::getPrefix() . "§aSelected are rotated (Took $time seconds)!");
    }
}
