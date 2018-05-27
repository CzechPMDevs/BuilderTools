<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Editor;
use buildertools\editors\Fixer;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class FixCommand
 * @package buildertools\commands
 */
class FixCommand extends Command implements PluginIdentifiableCommand {

    /**
     * FixCommand constructor.
     */
    public function __construct() {
        parent::__construct("/fix", "Fix selected area");
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

        if(!$sender->hasPermission("bt.cmd.fix")) {
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

        $firstPos = Selectors::getPosition($sender, 1);
        $secondPos = Selectors::getPosition($sender, 2);

        if($firstPos->getLevel()->getName() != $secondPos->getLevel()->getName()) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cPositions must be in same level");
            return;
        }

        /** @var Fixer $fixer */
        $fixer = BuilderTools::getEditor(Editor::FIXER);
        $fixer->fix($firstPos->getX(), $firstPos->getY(), $firstPos->getZ(), $secondPos->getX(), $secondPos->getY(), $secondPos->getZ(), $sender->getLevel(), $sender);
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}