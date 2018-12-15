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
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class BlockInfoCommand
 * @package czechpmdevs\buildertools\commands
 */
class BlockInfoCommand extends Command implements PluginIdentifiableCommand {

    /**
     * ReplaceCommand constructor.
     */
    public function __construct() {
        parent::__construct("/blockinfo", "Switch block info mode", null, ["/bi"]);
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
        if(!$sender->hasPermission("bt.cmd.blockinfo")) {
            $sender->sendMessage("§cYou do not have not permissions to use this command!");
            return;
        }

        Selectors::switchBlockInfoSelector($sender);
        $sender->sendMessage(BuilderTools::getPrefix() . "Block info mode turned " . (Selectors::isBlockInfoPlayer($sender) ? "on" : "off") . "!");
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}