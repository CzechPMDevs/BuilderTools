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
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\Printer;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class SphereCommand
 * @package buildertools\commands
 */
class SphereCommand extends BuilderToolsCommand {

    /**
     * SphereCommand constructor.
     */
    public function __construct() {
        parent::__construct("/sphere", "Create sphere", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage("§cUsage: §7//sphere <id1:dmg1,id2:dmg2:,...> <radius>");
            return;
        }
        $radius = isset($args[1]) ? (int)($args[1]) : 5;

        /** @var Printer $printer */
        $printer = BuilderTools::getEditor(Editor::PRINTER);
        /** @var EditorResult $result */
        $result = $printer->makeSphere($sender, $sender, $radius, $args[0]);
        $sender->sendMessage(BuilderTools::getPrefix()."§aSphere created in ".(string)round($result->time, 2)." (".(string)$result->countBlocks." changed)!");
    }
}