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

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Editor;
use buildertools\editors\Printer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class HsphereCommand
 * @package buildertools\commands
 */
class HsphereCommand extends Command implements PluginIdentifiableCommand {

    /**
     * SphereCommand constructor.
     */
    public function __construct() {
        parent::__construct("/hsphere", "Create hsphere", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in-game!");
            return;
        }
        if(!$sender->hasPermission("bt.cmd.sphere")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(empty($args[0])) {
            $sender->sendMessage("§cUsage: §7//hsphere <id1:dmg1,id2:dmg2:,...> <radius>");
            return;
        }
        $radius = isset($args[1]) ? intval($args[1]) : 5;
        $bargs = explode(",", strval($args[0]));
        $block = Item::fromString($bargs[array_rand($bargs, 1)])->getBlock();

        /** @var Printer $printer */
        $printer = BuilderTools::getEditor(Editor::PRINTER);
        $printer->draw($sender->asPosition(), $radius, $block, Printer::HSPHERE, false);
        $sender->sendMessage(BuilderTools::getPrefix()."§aSphere was created!");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}