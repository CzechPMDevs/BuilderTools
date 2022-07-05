<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
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
use czechpmdevs\buildertools\commands\ChunkCommand;
use czechpmdevs\buildertools\commands\ClearClipboardCommand;
use czechpmdevs\buildertools\commands\ClearInventoryCommand;
use czechpmdevs\buildertools\commands\CopyCommand;
use czechpmdevs\buildertools\commands\CubeCommand;
use czechpmdevs\buildertools\commands\CutCommand;
use czechpmdevs\buildertools\commands\CylinderCommand;
use czechpmdevs\buildertools\commands\DecorationCommand;
use czechpmdevs\buildertools\commands\DrainCommand;
use czechpmdevs\buildertools\commands\DrawCommand;
use czechpmdevs\buildertools\commands\FillCommand;
use czechpmdevs\buildertools\commands\FirstPositionCommand;
use czechpmdevs\buildertools\commands\FirstTargetingPositionCommand;
use czechpmdevs\buildertools\commands\FixCommand;
use czechpmdevs\buildertools\commands\FlipCommand;
use czechpmdevs\buildertools\commands\HelpCommand;
use czechpmdevs\buildertools\commands\HollowCubeCommand;
use czechpmdevs\buildertools\commands\HollowCylinderCommand;
use czechpmdevs\buildertools\commands\HollowPyramidCommand;
use czechpmdevs\buildertools\commands\HollowSphereCommand;
use czechpmdevs\buildertools\commands\IdCommand;
use czechpmdevs\buildertools\commands\IslandCommand;
use czechpmdevs\buildertools\commands\LineCommand;
use czechpmdevs\buildertools\commands\MaskCommand;
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
use czechpmdevs\buildertools\commands\SecondTargetingPositionCommand;
use czechpmdevs\buildertools\commands\SelectionCommand;
use czechpmdevs\buildertools\commands\SphereCommand;
use czechpmdevs\buildertools\commands\StackCommand;
use czechpmdevs\buildertools\commands\TreeCommand;
use czechpmdevs\buildertools\commands\UndoCommand;
use czechpmdevs\buildertools\commands\WallsCommand;
use czechpmdevs\buildertools\commands\WandCommand;
use czechpmdevs\buildertools\event\listener\EventListener;
use czechpmdevs\buildertools\item\WoodenAxe;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use czechpmdevs\buildertools\utils\IncompatibleConfigException;
use pocketmine\command\Command;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use ReflectionClass;
use function array_key_exists;
use function glob;
use function is_dir;
use function is_file;
use function is_string;
use function mkdir;
use function rename;
use function unlink;
use function version_compare;

class BuilderTools extends PluginBase {
	public const CURRENT_CONFIG_VERSION = "1.4.0.2";

	private static BuilderTools $instance;
	private static Configuration $configuration;
	private static Limits $limits;

	/** @var Command[] */
	private static array $commands = [];

	protected function onLoad(): void {
		$this->registerItems();
	}

	/** @noinspection PhpUnused */
	protected function onEnable(): void {
		BuilderTools::$instance = $this;

		$this->initConfig();
		$this->cleanCache();
		$this->registerCommands();
		$this->initMath();
		$this->initListener();
		$this->sendWarnings();
		$this->loadSchematicsManager();
	}

	/** @noinspection PhpUnused */
	protected function onDisable(): void {
		$this->cleanCache();
	}

	/**
	 * @throws IncompatibleConfigException
	 */
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
		if(!is_file($this->getDataFolder() . "data/bedrock_block_states_map.json")) {
			$this->saveResource("data/bedrock_block_states_map.json");
		}
		if(!is_file($this->getDataFolder() . "data/java_block_states_map.json")) {
			$this->saveResource("data/java_block_states_map.json");
		}

		$configuration = $this->getConfig()->getAll();
		if(!array_key_exists("config-version", $configuration) || !is_string($version = $configuration["config-version"]) || version_compare($version, BuilderTools::CURRENT_CONFIG_VERSION) < 0) {
			// Update is required
			@unlink($this->getDataFolder() . "config.yml.old");
			@rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.yml.old");

			$this->saveResource("config.yml", true);
			$this->getConfig()->reload();

			$this->getLogger()->notice("Config updated. Old config was renamed to 'config.yml.old'.");
		}

		self::$configuration = new Configuration($this->getConfig()->getAll());
		self::$limits = new Limits(
			self::$configuration->getIntProperty("clipboard-limit"),
			self::$configuration->getIntProperty("fill-limit")
		);
	}

	private function initListener(): void {
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}

	private function initMath(): void {
		Math::init();
	}

	private function registerCommands(): void {
		$map = $this->getServer()->getCommandMap();
		BuilderTools::$commands = [
			new BiomeCommand,
			new BlockInfoCommand,
			new CenterCommand,
			new ChunkCommand,
			new ClearClipboardCommand,
			new ClearInventoryCommand,
			new CopyCommand,
			new CubeCommand,
			new CutCommand,
			new CylinderCommand,
			new DecorationCommand,
			new DrainCommand,
			new DrawCommand,
			new FillCommand,
			new FirstPositionCommand,
			new FirstTargetingPositionCommand,
			new FixCommand,
			new FlipCommand,
			new HelpCommand,
			new HollowCubeCommand,
			new HollowCylinderCommand,
			new HollowPyramidCommand,
			new HollowSphereCommand,
			new IdCommand,
			new IslandCommand,
			new LineCommand,
			new MergeCommand,
			new MaskCommand,
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
			new SecondTargetingPositionCommand,
			new SelectionCommand,
			new SphereCommand,
			new StackCommand,
			new TreeCommand,
			new UndoCommand,
			new WallsCommand,
			new WandCommand
		];

		foreach(self::$commands as $command) {
			$map->register("BuilderTools", $command);
		}

		HelpCommand::buildPages();
	}

	public function registerItems(): void {
		ItemFactory::getInstance()->register(new WoodenAxe(new ItemIdentifier(ItemIds::WOODEN_AXE, 0), "Wooden Axe", ToolTier::WOOD()), true);

		$class = new ReflectionClass(VanillaItems::class);
		$prop = $class->getProperty("members");
		$prop->setAccessible(true);
		$prop->setValue(null);

		if(VanillaItems::WOODEN_AXE() instanceof WoodenAxe) {
			$this->getLogger()->debug("Wooden axe registered successfully");
		} else {
			throw new AssumptionFailedError("Unable to register WoodenAxe");
		}
	}

	private function sendWarnings(): void {
		if($this->getServer()->getConfigGroup()->getProperty("memory.async-worker-hard-limit") !== 0) {
			$this->getServer()->getLogger()->warning("We recommend to disable 'memory.async-worker-hard-limit' in pocketmine.yml. By disabling this option will be BuilderTools able to load bigger schematic files.");
		}
	}

	private function loadSchematicsManager(): void {
		SchematicsManager::lazyInit();
	}

	private function cleanCache(): void {
		if(!self::getConfiguration()->getBoolProperty("clean-cache")) {
			return;
		}

		$files = glob($this->getDataFolder() . "sessions/*.dat");
		if($files === false) {
			return;
		}

		/** @var string $offlineSession */
		foreach($files as $offlineSession) {
			unlink($offlineSession);
		}
	}

	/**
	 * @return Command[]
	 */
	public static function getAllCommands(): array {
		return self::$commands;
	}

	public static function getPrefix(): string {
		return "§7[BuilderTools] §a";
	}

	public static function getConfiguration(): Configuration {
		return self::$configuration;
	}

	public static function getLimits(): Limits {
		return self::$limits;
	}

	public static function getInstance(): BuilderTools {
		return self::$instance;
	}
}
