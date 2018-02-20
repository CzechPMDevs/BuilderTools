<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class DecorationCommand extends Command implements PluginIdentifiableCommand {

    public function __construct() {
        parent::__construct("/decoration", "Decoration commands", null, "/d");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in-game!");
            return;
        }
        if(!$sender->hasPermission("bt.cmd.decoration")) {
            $sender->sendMessage("§cYou do not have permissions to use this command!");
            return;
        }
        if(!(count($args) <= 2)) {
            $sender->sendMessage("§cUsage: §7//d <decoration: id1:dmg1,id2,...> <radius> <radius: square|cube> <percentage: 30%>");
            return;
        }
    }

    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}