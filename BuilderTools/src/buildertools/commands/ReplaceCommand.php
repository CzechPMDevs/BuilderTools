<?php

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Filler;
use buildertools\editors\Replacement;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class ReplaceCommand
 * @package buildertools\commands
 */
class ReplaceCommand extends Command implements PluginIdentifiableCommand {

    /**
     * ReplaceCommand constructor.
     */
    public function __construct() {
        parent::__construct("/replace", "Replace selected blocks", null, []);
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
        if(!$sender->hasPermission("bt.cmd.replace")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(empty($args[0]) || empty($args[1])) {
            $sender->sendMessage("§cUsage: §7//replace <BlocksToReplace - id1:meta1,id2:meta2,...> <Blocks - id1:meta1,id2:meta2,...>");
            return;
        }
        if(!Selectors::isSelected(1, $sender)) {
            $sender->sendMessage("§cFirst you need to select the first position.");
            return;
        }
        if(!Selectors::isSelected(2, $sender)) {
            $sender->sendMessage("§cFirst you need to select the second position.");
            return;
        }
        $firstPos = Selectors::getPosition($sender, 1);
        $secondPos = Selectors::getPosition($sender, 2);
        if($firstPos->getLevel()->getName() != $secondPos->getLevel()->getName()) {
            $sender->sendMessage("§cPositions must be in same level");
            return;
        }
        $filler = BuilderTools::getEditor("Replacement");
        if(!$filler instanceof Replacement) return;
        $count = $filler->replace($firstPos->getX(), $firstPos->getY(), $firstPos->getZ(), $secondPos->getX(), $secondPos->getY(), $secondPos->getZ(), $firstPos->getLevel(), $args[0], $args[1]);
        $sender->sendMessage("§aSelected area was filled ({$count} blocks changed)!");
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}