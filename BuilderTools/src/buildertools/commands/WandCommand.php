<?php

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class WandCommand
 * @package buildertools\commands
 */
class WandCommand extends Command implements PluginIdentifiableCommand {

    /**
     * WandCommand constructor.
     */
    public function __construct() {
        parent::__construct("/wand", "Switch wand tool", null, []);
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
        if(!$sender->hasPermission("bt.cmd.wand")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        Selectors::switchWandSelector($sender);
        $switch = Selectors::isWandSelector($sender) ? "ON" : "OFF";
        $sender->sendMessage(BuilderTools::getPrefix()."§aWand tool turned {$switch}!");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}