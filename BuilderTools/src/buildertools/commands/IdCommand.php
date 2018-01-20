<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class IdCommand
 * @package buildertools\commands
 */
class IdCommand extends Command implements PluginIdentifiableCommand {

    /**
     * IdCommand constructor.
     */
    public function __construct() {
        parent::__construct("/id", "Send id of item in your hands", null, []);
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
        if(!$sender->hasPermission("bt.cmd.clearinventory")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        $sender->sendMessage(BuilderTools::getPrefix()."§aID: §9{$sender->getInventory()->getItemInHand()->getId()}");
    }

    /**
     * @return Plugin|BuilderTools $plugin
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}