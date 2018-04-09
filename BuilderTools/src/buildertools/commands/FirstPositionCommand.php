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
 * Class FirstPositionCommand
 * @package buildertools\commands
 */
class FirstPositionCommand extends Command implements PluginIdentifiableCommand {

    /**
     * FirstPositionCommand constructor.
     */
    public function __construct() {
        parent::__construct("/pos1", "Select first position", null, ["/1"]);
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
        if(!$sender->hasPermission("bt.cmd.pos1")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        Selectors::addSelector($sender, 1, $position = new Position((int)round($sender->getX()), (int)round($sender->getY()), (int)round($sender->getZ()), $sender->getLevel()));
        $sender->sendMessage(BuilderTools::getPrefix()."§aSelected first position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}