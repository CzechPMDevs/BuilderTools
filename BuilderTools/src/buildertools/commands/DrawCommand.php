<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use buildertools\Selectors;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class DrawCommand extends Command implements PluginIdentifiableCommand {

    private $minBrush = 1;
    private $maxBrush = 6;

    /**
     * DrawCommand constructor.
     */
    public function __construct() {
        parent::__construct("/draw", "Draw witch blocks", null, []);
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
        if(!$sender->hasPermission("bt.cmd.draw")) {
            $sender->sendMessage("§cYou have not permissions to use this command!");
            return;
        }
        if(empty($args[0])) {
            $sender->sendMessage("§cUsage: §7//draw <brush: {$this->minBrush}-{$this->maxBrush} | on | off> <cube|sphere>");
            return;
        }
        if(!is_numeric($args[0]) && !in_array(strval($args[0]), ["on", "off"])) {
            $sender->sendMessage("§cUsage: §7//draw <brush: {$this->minBrush}-{$this->maxBrush} | on | off> <cube|sphere>");
            return;
        }
        if(is_numeric($args[0]) && intval($args[0]) >= $this->maxBrush && intval($args[0]) <= $this->minBrush) {
            $sender->sendMessage("§cBrush #{$args[0]} does not exists!");
            return;
        }
        if($args[0] == "off") {
            Selectors::removeDrawnigPlayer($sender);
            $sender->sendMessage(BuilderTools::getPrefix()."§aRemoved brush!");
            return;
        }
        $mode = 0;
        if(isset($args[1])) {
            if($args[1] == "cube") $mode = 0;
            if($args[1] == "sphere") $mode = 1;
        }
        $brush = 1;
        if(is_numeric($args[0])) {
            $brush = intval($args[0]);
        }

        Selectors::addDrawingPlayer($sender, $brush, $mode);
        $sender->sendMessage(BuilderTools::getPrefix()."§aSelected brush #{$brush}!");
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}