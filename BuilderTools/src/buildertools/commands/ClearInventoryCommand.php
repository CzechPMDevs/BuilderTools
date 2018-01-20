<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class ClearInventoryCommand extends Command implements PluginIdentifiableCommand {

    /**
     * ClearInventoryCommand constructor.
     */
    public function __construct() {
        parent::__construct("/clearinventory", "Clear inventory", null, ["/ci"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in-game!");
            return;
        }
        if(!$sender->hasPermission("bt.cmd.clearinventory")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
    }

    /**
     * @return Plugin|BuilderTools $plugin
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}