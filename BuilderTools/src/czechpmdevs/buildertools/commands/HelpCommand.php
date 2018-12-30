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

    public const COMMANDS_PER_PAGE = 5;

    /** @var string[] $pages */
    public static $pages = [];

    /**
     * HelpCommand constructor.
     */
    public function __construct() {
        parent::__construct("/commands", "Displays BuilderTools commands", null, ["/?", "buildertools", "/help"]);
    }

    public static function buildPages() {
        $commandsPerList = self::COMMANDS_PER_PAGE;

        $count = 1+(int)(count(BuilderTools::getAllCommands())/$commandsPerList);
        $list = 1;
        $command = 1;
        $text = "";
        $all = 0;

        //sort
        $commands = [];

        /**
         * @var Command $cmd
         */
        foreach (BuilderTools::getAllCommands() as $i => $cmd) {
            $commands[$i] = $cmd->getName();
        }

        asort($commands);

        foreach ($commands as $index => $name) {
            $all++;
            if($command == 1) {
                $text = "§2--- Showing help page {$list} of {$count} ---";
            }
            $text .= "\n§2/{$name}: §f" . BuilderTools::getAllCommands()[$index]->getDescription();
            if($command == self::COMMANDS_PER_PAGE || (count(BuilderTools::getAllCommands()) == $all)) {
                $command = 1;
                self::$pages[$list] = $text;
                $list++;
            }
            else {
                $command++;
            }
        }
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
        $page = 1;
        if(isset($args[0]) && is_numeric($args[0]) && (int)$args[0] <= ((int)(count(BuilderTools::getAllCommands())/self::COMMANDS_PER_PAGE))) {
            $page = (int)$args[0];
        }

        $sender->sendMessage(self::$pages[$page]);
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}
