<?php

/**
 * Copyright 2018 CzechPMDevs
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace buildertools;

use buildertools\commands\ClearInventoryCommand;
use buildertools\commands\CopyCommand;
use buildertools\commands\CubeCommand;
use buildertools\commands\DecorationCommand;
use buildertools\commands\DrawCommand;
use buildertools\commands\FillCommand;
use buildertools\commands\FirstPositionCommand;
use buildertools\commands\FixCommand;
use buildertools\commands\FlipCommand;
use buildertools\commands\HelpCommand;
use buildertools\commands\IdCommand;
use buildertools\commands\NaturalizeCommand;
use buildertools\commands\PasteCommand;
use buildertools\commands\RedoCommand;
use buildertools\commands\ReplaceCommand;
use buildertools\commands\RotateCommand;
use buildertools\commands\SecondPositionCommand;
use buildertools\commands\SphereCommand;
use buildertools\commands\TreeCommand;
use buildertools\commands\UndoCommand;
use buildertools\commands\WandCommand;
use buildertools\editors\Canceller;
use buildertools\editors\Copier;
use buildertools\editors\Decorator;
use buildertools\editors\Editor;
use buildertools\editors\Filler;
use buildertools\editors\Fixer;
use buildertools\editors\Naturalizer;
use buildertools\editors\Printer;
use buildertools\editors\Replacement;
use buildertools\event\listener\EventListener;
use buildertools\utils\ConfigManager;
use pocketmine\plugin\PluginBase;

/**
 * Class BuilderTools
 * @package buildertools
 */
class BuilderTools extends PluginBase {

    /** @var  BuilderTools $instance */
    private static $instance;

    /** @var  string $prefix */
    private static $prefix;

    /** @var  Editor[] $editors */
    private static $editors = [];

    /** @var EventListener $listener */
    private static $listener;

    /** @var ConfigManager $configManager */
    private $configManager;

    public function onEnable() {
        self::$instance = $this;
        self::$prefix = "§7[BuilderTools] §a";
        $this->sendLoadingInfo();
        $this->registerCommands();
        $this->initListner();
        $this->initConfig();
        $this->registerEditors();
        if($this->isEnabled()) {
            $this->getLogger()->info("§a--> Loaded!");
        }
        else {
            $this->getLogger()->critical("§4Submit issue to github.com/CzechPMDevs/BuilderTools/issues  to fix this error!");
        }
    }

    private function sendLoadingInfo() {
        $text = strval(
            "\n".
            "--------------------------------\n".
            "CzechPMDevs >>> BuilderTools\n".
            "Plugin like WorldEdit for PocketMine servers\n".
            "Authors: VixikCZ\n".
            "Version: ".$this->getDescription()->getVersion()."\n".
            "Status: Loading...\n".
            "--------------------------------"
        );
        $this->getLogger()->info($text);
    }

    private function registerEditors() {
        self::$editors["Filler"] = new Filler;
        self::$editors["Printer"] = new Printer;
        self::$editors["Replacement"] = new Replacement;
        self::$editors["Naturalizer"] = new Naturalizer;
        self::$editors["Copier"] = new Copier;
        self::$editors["Canceller"] = new Canceller;
        self::$editors["Decorator"] = new Decorator;
        self::$editors["Fixer"] = new Fixer;
    }

    private function initListner() {
        $this->getServer()->getPluginManager()->registerEvents(self::$listener = new EventListener, $this);
    }

    private function initConfig() {
        $this->configManager = new ConfigManager($this);
    }

    private function registerCommands() {
        $map = $this->getServer()->getCommandMap();
        $map->register("BuilderTools", new FirstPositionCommand);
        $map->register("BuilderTools", new SecondPositionCommand);
        $map->register("BuilderTools", new WandCommand);
        $map->register("BuilderTools", new FillCommand);
        $map->register("BuilderTools", new HelpCommand);
        $map->register("BuilderTools", new DrawCommand);
        $map->register("BuilderTools", new SphereCommand);
        $map->register("BuilderTools", new ReplaceCommand);
        $map->register("BuilderTools", new IdCommand);
        $map->register("BuilderTools", new ClearInventoryCommand);
        $map->register("BuilderTools", new NaturalizeCommand);
        $map->register("BuilderTools", new CopyCommand);
        $map->register("BuilderTools", new PasteCommand);
        $map->register("BuilderTools", new RotateCommand);
        $map->register("BuilderTools", new UndoCommand);
        $map->register("BuilderTools", new RedoCommand);
        $map->register("BuilderTools", new TreeCommand);
        $map->register("BuilderTools", new DecorationCommand);
        $map->register("BuilderTools", new FlipCommand);
        $map->register("BuilderTools", new FixCommand);
        $map->register("BuilderTools", new CubeCommand);
    }

    /**
     * @param string $name
     * @return Editor $editor
     */
    public static function getEditor(string $name):Editor {
        return self::$editors[$name];
    }

    /**
     * @return string $prefix
     */
    public static function getPrefix():string {
        return self::$prefix;
    }

    /**
     * @return EventListener $listener
     */
    public static function getListener():EventListener {
        return self::$listener;
    }

    /**
     * @return BuilderTools $instance
     */
    public static function getInstance():BuilderTools {
        return self::$instance;
    }
}