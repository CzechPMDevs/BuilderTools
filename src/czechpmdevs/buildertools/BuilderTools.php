<?php

/**
 * Copyright (C) 2018-2021  CzechPMDevs
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

use czechpmdevs\buildertools\commands\BiomeCommand;
use czechpmdevs\buildertools\commands\BlockInfoCommand;
use czechpmdevs\buildertools\commands\CenterCommand;
use czechpmdevs\buildertools\commands\ClearInventoryCommand;
use czechpmdevs\buildertools\commands\CopyCommand;
use czechpmdevs\buildertools\commands\CubeCommand;
use czechpmdevs\buildertools\commands\CutCommand;
use czechpmdevs\buildertools\commands\CylinderCommand;
use czechpmdevs\buildertools\commands\DecorationCommand;
use czechpmdevs\buildertools\commands\DrawCommand;
use czechpmdevs\buildertools\commands\FillCommand;
use czechpmdevs\buildertools\commands\FirstPositionCommand;
use czechpmdevs\buildertools\commands\FixCommand;
use czechpmdevs\buildertools\commands\FlipCommand;
use czechpmdevs\buildertools\commands\HelpCommand;
use czechpmdevs\buildertools\commands\HollowCubeCommand;
use czechpmdevs\buildertools\commands\HollowCylinderCommand;
use czechpmdevs\buildertools\commands\HollowPyramidCommand;
use czechpmdevs\buildertools\commands\HollowSphereCommand;
use czechpmdevs\buildertools\commands\IdCommand;
use czechpmdevs\buildertools\commands\MergeCommand;
use czechpmdevs\buildertools\commands\MoveCommand;
use czechpmdevs\buildertools\commands\NaturalizeCommand;
use czechpmdevs\buildertools\commands\OutlineCommand;
use czechpmdevs\buildertools\commands\PasteCommand;
use czechpmdevs\buildertools\commands\PyramidCommand;
use czechpmdevs\buildertools\commands\RedoCommand;
use czechpmdevs\buildertools\commands\ReplaceCommand;
use czechpmdevs\buildertools\commands\RotateCommand;
use czechpmdevs\buildertools\commands\SchematicCommand;
use czechpmdevs\buildertools\commands\SecondPositionCommand;
use czechpmdevs\buildertools\commands\SphereCommand;
use czechpmdevs\buildertools\commands\StackCommand;
use czechpmdevs\buildertools\commands\TreeCommand;
use czechpmdevs\buildertools\commands\UndoCommand;
use czechpmdevs\buildertools\commands\WallsCommand;
use czechpmdevs\buildertools\commands\WandCommand;
use czechpmdevs\buildertools\event\listener\EventListener;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use function array_key_exists;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function rename;
use function unlink;
use function version_compare;

class BuilderTools extends PluginBase {

    public const CURRENT_CONFIG_VERSION = "1.2.0.3";

    /** @var BuilderTools */
    private static BuilderTools $instance;
    /** @var string */
    private static string $prefix = "§7[BuilderTools] §a";

    /** @var EventListener */
    private static EventListener $listener;

    /** @var Command[] */
    private static array $commands = [];

    /** @noinspection PhpUnused */
    public function onEnable() {
        BuilderTools::$instance = $this;

        $this->initConfig();
        $this->cleanCache();
        $this->registerCommands();
        $this->initListener();
        $this->sendWarnings();
        $this->loadSchematicsManager();
    }

    /** @noinspection PhpUnused */
    public function onDisable() {
        $this->cleanCache();
    }

    private function initConfig(): void {
        if(!is_dir($this->getDataFolder() . "schematics")) {
            @mkdir($this->getDataFolder() . "schematics");
        }
        if(!is_dir($this->getDataFolder() . "sessions")) {
            @mkdir($this->getDataFolder() . "sessions");
        }
        if(!is_dir($this->getDataFolder() . "data")) {
            @mkdir($this->getDataFolder() . "data");
        }
        if(!is_file($this->getDataFolder() . "data/internalId2StatesMap.serialized")) {
            $this->saveResource("data/internalId2StatesMap.serialized");
        }
        if(!is_file($this->getDataFolder() . "data/states2InternalIdMap.serialized")) {
            $this->saveResource("data/states2InternalIdMap.serialized");
        }

        $configuration = $this->getConfig()->getAll();
        if(
            !array_key_exists("config-version", $configuration) ||
            version_compare((string)$configuration["config-version"], BuilderTools::CURRENT_CONFIG_VERSION) < 0
        ) {
            // Update is required
            @unlink($this->getDataFolder() . "config.yml.old");
            @rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.yml.old");

            $this->saveResource("config.yml", true);
            $this->getConfig()->reload();

            $this->getLogger()->notice("Config updated. Old config was renamed to 'config.yml.old'.");
        }
    }

    private function initListener(): void {
        $this->getServer()->getPluginManager()->registerEvents(BuilderTools::$listener = new EventListener(), $this);
    }

    private function registerCommands(): void {
        $map = $this->getServer()->getCommandMap();
        BuilderTools::$commands = [
            new BiomeCommand,
            new BlockInfoCommand,
            new CenterCommand,
            new ClearInventoryCommand,
            new CopyCommand,
            new CubeCommand,
            new CutCommand,
            new CylinderCommand,
            new DecorationCommand,
            new DrawCommand,
            new FillCommand,
            new FirstPositionCommand,
            new FixCommand,
            new FlipCommand,
            new HelpCommand,
            new HollowCubeCommand,
            new HollowCylinderCommand,
            new HollowPyramidCommand,
            new HollowSphereCommand,
            new IdCommand,
            new MergeCommand,
            new MoveCommand,
            new NaturalizeCommand,
            new OutlineCommand,
            new PasteCommand,
            new PyramidCommand,
            new RedoCommand,
            new ReplaceCommand,
            new RotateCommand,
            new SchematicCommand,
            new SecondPositionCommand,
            new SphereCommand,
            new StackCommand,
            new TreeCommand,
            new UndoCommand,
            new WallsCommand,
            new WandCommand
        ];

        foreach (BuilderTools::$commands as $command) {
            $map->register("BuilderTools", $command);
        }

        HelpCommand::buildPages();
    }

    public function sendWarnings(): void {
        if($this->getServer()->getProperty("memory.async-worker-hard-limit") != 0) {
            $this->getServer()->getLogger()->warning("We recommend to disable 'memory.async-worker-hard-limit' in pocketmine.yml. By disabling this option will be BuilderTools able to load bigger schematic files.");
        }
    }

    public function loadSchematicsManager(): void {
        SchematicsManager::lazyInit();
    }

    public function cleanCache(): void {
        if(BuilderTools::getConfiguration()["clean-cache"] ?? false) {
            return;
        }

        $files = glob($this->getDataFolder() . "sessions/*.dat");
        if($files === false) {
            return;
        }

        /** @var string $offlineSession */
        foreach ($files as $offlineSession) {
            unlink($offlineSession);
        }
    }

    /**
     * @return Command[]
     */
    public static function getAllCommands(): array {
        return BuilderTools::$commands;
    }

    public static function getPrefix(): string {
        return BuilderTools::$prefix;
    }

    /**
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * @phpstan-return mixed[]
     */
    public static function getConfiguration(): array {
        return BuilderTools::$instance->getConfig()->getAll();
    }

    public static function getInstance(): BuilderTools {
        return BuilderTools::$instance;
    }
}
