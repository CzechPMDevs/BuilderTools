<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Editor;
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
     * SphereCommand constructor.
     */
    public function __construct() {
        parent::__construct("/hsphere", "Create hsphere", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in-game!");
            return;
        }
        if(!$sender->hasPermission("bt.cmd.sphere")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(empty($args[0])) {
            $sender->sendMessage("§cUsage: §7//hsphere <id1:dmg1,id2:dmg2:,...> <radius>");
            return;
        }
        $radius = isset($args[1]) ? intval($args[1]) : 5;
        $bargs = explode(",", strval($args[0]));
        $block = Item::fromString($bargs[array_rand($bargs, 1)])->getBlock();

        /** @var Printer $printer */
        $printer = BuilderTools::getEditor(Editor::PRINTER);
        $printer->draw($sender->asPosition(), $radius, $block, Printer::HSPHERE, false);
        $sender->sendMessage(BuilderTools::getPrefix()."§aSphere was created!");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}