<?php

/**
 * Copyright 2018 CzechPMDevs
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
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class CubeCommand
 * @package buildertools\commands
 */
class CubeCommand extends Command implements PluginIdentifiableCommand {

    /**
     * CubeCommand constructor.
     */
    public function __construct() {
        parent::__construct("/cube", "Create cube", null, []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in-game!");
            return;
        }
        if(!$sender->hasPermission("bt.cmd.cube")) {
            $sender->sendMessage("§cYou do have not permissions to use this command!");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage("§7Usage: §c//cube <id1:dmg1,id2:dmg2,...> <radius>");
            return;
        }
        $radius = isset($args[1]) ? $args[1] : 5;

        /** @var Printer $printer */
        $printer = BuilderTools::getEditor(Editor::PRINTER);
        /** @var EditorResult $result */
        $result = $printer->makeCube($sender, $sender->asPosition(), $radius, (string)$args[0]);
        $sender->sendMessage(BuilderTools::getPrefix()."§aCube created in ".(string)round($result->time, 2)." (".(string)$result->countBlocks." block changed)!");
    }

    /**
     * @return Plugin|BuilderTools $plugin
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}