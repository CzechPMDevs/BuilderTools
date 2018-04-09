<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Decorator;
use buildertools\editors\Editor;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class DecorationCommand extends Command implements PluginIdentifiableCommand {

    public function __construct() {
        parent::__construct("/decoration", "Decoration commands", null, ["/d"]);
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

        $decorator->addDecoration($sender, $args[0], intval($args[1]), $percentage);

        $sender->sendMessage(BuilderTools::getPrefix()."§aDecoration placed!");
    }

    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}