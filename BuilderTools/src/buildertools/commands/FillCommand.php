<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\editors\Editor;
use buildertools\editors\Filler;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Class FillCommand
 * @package buildertools\commands
 */
class FillCommand extends Command implements PluginIdentifiableCommand {

    /**
     * FillCommand constructor.
     */
    public function __construct() {
        parent::__construct("/fill", "Fill selected positions.", null, ["/set", "/change"]);
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
        if(!$sender->hasPermission("bt.cmd.fill")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(empty($args[0])) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cUsage: §7//fill <id1:meta1,id2:meta2,...> [async = false]");
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

        $async = false;

        if(isset($args[1])) {
            if(is_bool(boolval($args[1]))) {
                $async = boolval($args[1]);
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);

        if($async) {
            $filler->fillAsync($firstPos, $secondPos, $firstPos->getLevel(), $args[0], $sender);
            return;
        }

        $blocks = $filler->prepareFill($firstPos->asVector3(), $secondPos->asVector3(), $firstPos->getLevel(), $args[0]);
        $result = $filler->fill($sender, $blocks, true);

        $sender->sendMessage("§a> Selected area filled, " . $result->countBlocks ." changed in " . round($result->time, 4) . "sec");
    }

    /**
     * @return Plugin|BuilderTools $builderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}