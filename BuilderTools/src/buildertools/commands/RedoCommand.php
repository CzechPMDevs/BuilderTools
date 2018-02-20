<?php

declare(strict_types=1);

namespace buildertools\commands;

use buildertools\BuilderTools;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;

class RedoCommand extends Command implements PluginIdentifiableCommand {

    public function __construct() {
        parent::__construct("redo", "Redo last BuilderTools action", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {

    }

    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}
