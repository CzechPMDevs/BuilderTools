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

use czechpmdevs\buildertools\commands\biome\BiomeCommand;
use czechpmdevs\buildertools\commands\clipboard\ClearClipboardCommand;
use czechpmdevs\buildertools\commands\clipboard\CopyCommand;
use czechpmdevs\buildertools\commands\clipboard\CutCommand;
use czechpmdevs\buildertools\commands\clipboard\FlipCommand;
use czechpmdevs\buildertools\commands\clipboard\MergeCommand;
use czechpmdevs\buildertools\commands\clipboard\PasteCommand;
use czechpmdevs\buildertools\commands\clipboard\RotateCommand;
use czechpmdevs\buildertools\commands\generation\CubeCommand;
use czechpmdevs\buildertools\commands\generation\CylinderCommand;
use czechpmdevs\buildertools\commands\generation\DrawCommand;
use czechpmdevs\buildertools\commands\generation\HollowCubeCommand;
use czechpmdevs\buildertools\commands\generation\HollowCylinderCommand;
use czechpmdevs\buildertools\commands\generation\HollowPyramidCommand;
use czechpmdevs\buildertools\commands\generation\HollowSphereCommand;
use czechpmdevs\buildertools\commands\generation\IslandCommand;
use czechpmdevs\buildertools\commands\generation\PyramidCommand;
use czechpmdevs\buildertools\commands\generation\SphereCommand;
use czechpmdevs\buildertools\commands\generation\TreeCommand;
use czechpmdevs\buildertools\commands\HelpCommand;
use czechpmdevs\buildertools\commands\history\RedoCommand;
use czechpmdevs\buildertools\commands\history\UndoCommand;
use czechpmdevs\buildertools\commands\region\CenterCommand;
use czechpmdevs\buildertools\commands\region\DecorationCommand;
use czechpmdevs\buildertools\commands\region\DrainCommand;
use czechpmdevs\buildertools\commands\region\FillCommand;
use czechpmdevs\buildertools\commands\region\LineCommand;
use czechpmdevs\buildertools\commands\region\MoveCommand;
use czechpmdevs\buildertools\commands\region\NaturalizeCommand;
use czechpmdevs\buildertools\commands\region\OutlineCommand;
use czechpmdevs\buildertools\commands\region\ReplaceCommand;
use czechpmdevs\buildertools\commands\region\StackCommand;
use czechpmdevs\buildertools\commands\region\WallsCommand;
use czechpmdevs\buildertools\commands\schematics\SchematicCommand;
use czechpmdevs\buildertools\commands\selection\ChunkCommand;
use czechpmdevs\buildertools\commands\selection\FirstPositionCommand;
use czechpmdevs\buildertools\commands\selection\FirstTargetingPositionCommand;
use czechpmdevs\buildertools\commands\selection\SecondPositionCommand;
use czechpmdevs\buildertools\commands\selection\SecondTargetingPositionCommand;
use czechpmdevs\buildertools\commands\selection\SelectionCommand;
use czechpmdevs\buildertools\commands\selection\WandCommand;
use czechpmdevs\buildertools\commands\utility\BlockInfoCommand;
use czechpmdevs\buildertools\commands\utility\ClearInventoryCommand;
use czechpmdevs\buildertools\commands\utility\IdCommand;
use czechpmdevs\buildertools\commands\utility\MaskCommand;
use czechpmdevs\buildertools\event\listener\EventListener;
use czechpmdevs\buildertools\item\WoodenAxe;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use czechpmdevs\buildertools\utils\IncompatibleConfigException;
use pocketmine\command\Command;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier as IID;
use pocketmine\item\ItemTypeIds as Ids;
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
	public const RESOURCE_DATA_PATH = "/plugin_data/BuilderTools/data/";

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
		if(!is_file($this->getDataFolder() . "data/legacy_java_to_bedrock_id_map.json")) {
			$this->saveResource("data/legacy_java_to_bedrock_id_map.json");
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
		$class = new ReflectionClass(VanillaItems::class);
		$prop = $class->getProperty("members");
		$prop->setAccessible(true);

		/** @var array<string, Item> $val */
		$val = $prop->getValue();
		$val["WOODEN_AXE"] = $axe = new WoodenAxe(new IID(Ids::WOODEN_AXE), "Wooden Axe", ToolTier::WOOD());

		$prop->setValue($val);

		if(VanillaItems::WOODEN_AXE() instanceof WoodenAxe) {
			$this->getLogger()->debug("Wooden axe registered successfully");
		} else {
			throw new AssumptionFailedError("Unable to register WoodenAxe");
		}
//
//		GlobalItemDataHandlers::getSerializer()->map($axe, fn() => new SavedItemData("buildertools:wand_axe"));
//		GlobalItemDataHandlers::getDeserializer()->map("buildertools:wand_axe", fn() => $axe);
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
