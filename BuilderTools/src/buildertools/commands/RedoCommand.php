<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Canceller;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class UndoCommand
 * @package buildertools\commands
 */
class RedoCommand extends Command implements PluginIdentifiableCommand {

    /**
     * UndoCommand constructor.
     */
    public function __construct() {
        parent::__construct("/redo", "Redo last BuilderTools actions", null, []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in-game!");
            return;
        }
        if(!$sender->hasPermission("bt.cmd.undo")) {
            $sender->sendMessage("§cYou do not have permissions to use this command!");
            return;
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");

        $canceller->redo($sender);
    }

    /**
     * @return Plugin&BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}
