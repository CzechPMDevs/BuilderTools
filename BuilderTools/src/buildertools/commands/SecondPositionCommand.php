<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\Plugin;


/**
 * Class SecondPositionCommand
 * @package buildertools\commands
 */
class SecondPositionCommand extends Command implements PluginIdentifiableCommand {

    /**
     * SecondPositionCommand constructor.
     */
    public function __construct() {
        parent::__construct("/pos2", "Select second position", null, ["/2"]);
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
        if(!$sender->hasPermission("bt.cmd.pos2")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        Selectors::addSelector($sender, 2, $position = new Position((int)round($sender->getX()), (int)round($sender->getY()), (int)round($sender->getZ()), $sender->getLevel()));
        $sender->sendMessage(BuilderTools::getPrefix()."§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}