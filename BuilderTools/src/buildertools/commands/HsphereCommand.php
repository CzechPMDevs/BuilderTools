<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Printer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class HsphereCommand
 * @package buildertools\commands
 */
class HsphereCommand extends Command implements PluginIdentifiableCommand {

    /**
     * HsphereCommand constructor.
     */
    public function __construct() {
        parent::__construct("/hsphere", "Create hollow sphere", null, []);
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
        if(!$sender->hasPermission("bt.cmd.hsphere")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(empty($args[0])) {
            $sender->sendMessage("§cUsage: §7//sphere <id1:damage1,id2:damage2:,...> <radius>");
            return;
        }
        $radius = isset($args[1]) ? $args[1] : 5;
        $bargs = explode(",", strval($args[0]));
        $block = Item::fromString($bargs[array_rand($bargs, 1)])->getBlock();
        $printer = BuilderTools::getEditor("Printer");
        if($printer instanceof Printer) {
            $printer->draw($sender->asPosition(), $radius, $block, Printer::HSPHERE);
        }
        $sender->sendMessage("§aHsphere was created!");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}