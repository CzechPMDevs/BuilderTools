<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;

/**
 * Class BuilderToolsCommand
 * @package czechpmdevs\buildertools\commands
 */
abstract class BuilderToolsCommand extends Command implements PluginIdentifiableCommand {

    /**
     * BuilderToolsCommand constructor.
     * @param string $name
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, string $description = "", string $usageMessage = null, $aliases = []) {
        $this->setPermission($this->getPerms($name));
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender->hasPermission($this->getPermission())) {
            $sender->sendMessage((string)$this->getPermissionMessage());
            return;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function getPerms(string $name) {
        return "bt.cmd." . str_replace("/", "", strtolower($name));
    }

    /**
     * @return Plugin|BuilderTools
     */
    public function getPlugin(): Plugin {
        return BuilderTools::getInstance();
    }
}