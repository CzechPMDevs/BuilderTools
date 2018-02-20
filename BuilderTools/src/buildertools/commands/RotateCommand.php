<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Copier;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class RotateCommand
 * @package buildertools\commands
 */
class RotateCommand extends Command implements PluginIdentifiableCommand {

    /**
     * RotateCommand constructor.
     */
    public function __construct() {
        parent::__construct("/rotate", "Rotate selected area", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            return;
        }

        if(!$sender->hasPermission("bt.cmd.rotate")) {
            $sender->sendMessage("§cYou do not have permissions to use this command.");
            return;
        }

        /** @var Copier $copier */
        $copier = BuilderTools::getEditor("Copier");

        $copier->addToRotate($sender);
    }

    /**
     * @return Plugin|BuilderTools $plugin
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}
