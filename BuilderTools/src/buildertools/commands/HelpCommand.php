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
 * Class HelpCommand
 * @package buildertools\commands
 */
class HelpCommand extends Command implements PluginIdentifiableCommand {

    /**
     * HelpCommand constructor.
     */
    public function __construct() {
        parent::__construct("/help", "Displays BuilderTools commands", null, ["/?", "buildertools"]);
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
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}