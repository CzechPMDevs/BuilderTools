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

namespace czechpmdevs\buildertools;

use czechpmdevs\buildertools\commands\BlockInfoCommand;
use czechpmdevs\buildertools\commands\ClearInventoryCommand;
use czechpmdevs\buildertools\commands\CopyCommand;
use czechpmdevs\buildertools\commands\CubeCommand;
use czechpmdevs\buildertools\commands\DrawCommand;
use czechpmdevs\buildertools\commands\FillCommand;
use czechpmdevs\buildertools\commands\FirstPositionCommand;
use czechpmdevs\buildertools\commands\FixCommand;
use czechpmdevs\buildertools\commands\FlipCommand;
use czechpmdevs\buildertools\commands\HelpCommand;
use czechpmdevs\buildertools\commands\IdCommand;
use czechpmdevs\buildertools\commands\MergeCommand;
use czechpmdevs\buildertools\commands\NaturalizeCommand;
use czechpmdevs\buildertools\commands\PasteCommand;
use czechpmdevs\buildertools\commands\RedoCommand;
use czechpmdevs\buildertools\commands\ReplaceCommand;
use czechpmdevs\buildertools\commands\RotateCommand;
use czechpmdevs\buildertools\commands\SecondPositionCommand;
use czechpmdevs\buildertools\commands\SphereCommand;
use czechpmdevs\buildertools\commands\TreeCommand;
use czechpmdevs\buildertools\commands\UndoCommand;
use czechpmdevs\buildertools\commands\WandCommand;
use czechpmdevs\buildertools\editors\Canceller;
use czechpmdevs\buildertools\editors\Copier;
use czechpmdevs\buildertools\editors\Decorator;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Filler;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\editors\Naturalizer;
use czechpmdevs\buildertools\editors\Printer;
use czechpmdevs\buildertools\editors\Replacement;
use czechpmdevs\buildertools\event\listener\EventListener;
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

    public function onEnable() {
        self::$instance = $this;
        self::$prefix = "§7[BuilderTools] §a";
        $this->registerCommands();
        $this->initListner();
        $this->registerEditors();
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
        #$map->register("BuilderTools", new DecorationCommand); taken down due to release
        $map->register("BuilderTools", new FlipCommand);
        $map->register("BuilderTools", new FixCommand);
        $map->register("BuilderTools", new CubeCommand);
        $map->register("BuilderTools", new MergeCommand);
        $map->register("BuilderTools", new BlockInfoCommand);
    }

    /**
     * @param string $name
     * @return Editor $editor
     */
    public static function getEditor(string $name): Editor {
        return self::$editors[$name];
    }

    /**
     * @return string $prefix
     */
    public static function getPrefix(): string {
        return self::$prefix;
    }

    /**
     * @return EventListener $listener
     */
    public static function getListener(): EventListener {
        return self::$listener;
    }

    /**
     * @return BuilderTools $instance
     */
    public static function getInstance(): BuilderTools {
        return self::$instance;
    }
}
