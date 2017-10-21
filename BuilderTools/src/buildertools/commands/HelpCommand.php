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
        if(empty($args[0])) {
            $sender->sendMessage("---- BuilderTools Commands (1/3) ----\n".
                "§2//draw: §fDraw witch blocks\n".
                "§2//fill: §fFill selected position\n".
                "§2//help: §fDisplays BuilderTools commands\n"/*.
                "§2//hsphere: §fCreate hollow sphere"*/);
            return;
        }
        if($args[0] == "2") {
            $sender->sendMessage("---- BuilderTools Commands (2/3) ----\n".
                "§2//pos1: §fSelect first position\n".
                "§2//pos2: §fSelect second position\n".
                "§2//replace: §fReplace selected blocks\n".
                "§2//sphere: §fCreate sphere");
            return;
        }
        if($args[0] == "3") {
            $sender->sendMessage("---- §fBuilderTools Commands (3/3) ----\n".
                "§2//wand: §fSwitch wand tool");
            return;
        }
        $sender->sendMessage("---- §fBuilderTools Commands (1/3) ----\n".
            "§2//draw: §fDraw witch blocks\n".
            "§2//fill: §fFill selected position\n".
            "§2//help: §fDisplays BuilderTools commands\n"/*.
            "§2//hsphere: §fCreate hollow sphere"*/);
        return;
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}