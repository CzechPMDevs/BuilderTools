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
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class HelpCommand
 * @package buildertools\commands
 */
class HelpCommand extends Command implements PluginIdentifiableCommand {

    /**
     * HelpCommand constructor.
     */
    public function __construct() {
        parent::__construct("/commands", "Displays BuilderTools commands", null, ["/?", "buildertools"]);
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
        }
        if(!$sender->hasPermission("bt.cmd.help")) {
            $sender->sendMessage("§cYou do have not permissions to use this command!");
            return;
        }
        /* FIXING TWO COMMANDS IN PROGRESS
         * if(empty($args[0])) {
            $sender->sendMessage("---- BuilderTools Commands (1/5) ----\n".
                "§2//ci: §fClears your inventory\n".
                "§2//copy: §fCopy selected area\n".
                "§2//cube: §fCreate cube\n".
                "§2//decoration: §fNatural decoration commands");
            return;
        }
        if($args[0] == "2") {
            $sender->sendMessage("---- BuilderTools Commands (2/5) ----\n".
                "§2//draw: §fSelect first position\n".
                "§2//fill: §fFill selected area\n".
                "§2//flip: §fFlip copied area\n".
                "§2//commands: §fDisplays help pages");
            return;
        }
        if($args[0] == "3") {
            $sender->sendMessage("---- §fBuilderTools Commands (3/5) ----\n".
                "§2//id §fDisplays item id\n".
                "§2//naturalize §fNaturalize selected area\n".
                "§2//paste §fPaste selected area\n".
                "§2//pos1 §fSelect first position");
            return;
        }
        if($args[0] == "4") {
            $sender->sendMessage("---- §fBuilderTools Commands (4/5) ----\n".
                "§2//pos2: §fSelect second position\n".
                "§2//redo: §fRedo last BuilderTools action\n".
                "§2//replace: §fReplace blocks in selected area\n".
                "§2//sphere: §fCreate sphere\n");
            return;
        }
        if($args[0] == "5") {
            $sender->sendMessage("---- §fBuilderTools Commands (4/5) ----\n".
                "§2//undo: §fUndo last action\n".
                "§2//wand: §fSwitch wand command\n");
            return;
        }*/
        if(!isset($args[0])) {
            $sender->sendMessage("---- BuilderTools Commands (1/5) ----\n".
                "§2//ci: §fClears your inventory\n".
                "§2//copy: §fCopy selected area\n".
                "§2//cube: §fCreate cube\n".
                "§2//draw: §fSelect first position");
            return;
        }
        if($args[0] == "2") {
            $sender->sendMessage("---- BuilderTools Commands (2/5) ----\n".
                "§2//fill: §fFill selected area\n".
                "§2//flip: §fFlip copied area\n".
                "§2//commands: §fDisplays help pages\n".
                "§2//id §fDisplays item id");
            return;
        }
        if($args[0] == "3") {
            $sender->sendMessage("---- §fBuilderTools Commands (3/5) ----\n".
                "§2//merge §fMerge copied area\n".
                "§2//naturalize §fNaturalize selected area\n".
                "§2//paste §fPaste selected area\n".
                "§2//pos1 §fSelect first position"
            );
            return;
        }
        if($args[0] == "4") {
            $sender->sendMessage("---- §fBuilderTools Commands (4/5) ----\n".
                "§2//pos2: §fSelect second position\n".
                "§2//replace: §fReplace blocks in selected area\n".
                "§2//sphere: §fCreate sphere\n" .
                "§2//undo: §fUndo last action"
            );
            return;
        }
        if($args[0] == "5") {
            $sender->sendMessage("---- §fBuilderTools Commands (4/5) ----\n".
                "§2//wand: §fSwitch wand command"
            );
            return;
        }

        return;
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}
