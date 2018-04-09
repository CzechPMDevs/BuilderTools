<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Copier;
use buildertools\editors\Editor;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class CopyCommand
 * @package buildertools\commands
 */
class CopyCommand extends Command implements PluginIdentifiableCommand {

    /**
     * CopyCommand constructor.
     */
    public function __construct() {
        parent::__construct("/copy", "Copy selected area", null, []);
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
        if(!$sender->hasPermission("bt.cmd.copy")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(!Selectors::isSelected(1, $sender)) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cFirst you need to select the first position.");
            return;
        }
        if(!Selectors::isSelected(2, $sender)) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cFirst you need to select the second position.");
            return;
        }
        $pos1 = Selectors::getPosition($sender, 1);
        $pos2 = Selectors::getPosition($sender, 2);
        /** @var Copier $copier */
        $copier = BuilderTools::getEditor(Editor::COPIER);
        $copier->copy($pos1->getX(), $pos1->getY(), $pos1->getZ(), $pos2->getX(), $pos2->getY(), $pos2->getZ(), $sender);
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}