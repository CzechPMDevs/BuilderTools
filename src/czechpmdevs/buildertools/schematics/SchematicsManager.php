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

namespace czechpmdevs\buildertools\schematics;

use Closure;
use czechpmdevs\buildertools\async\AsyncQueue;
use czechpmdevs\buildertools\async\schematics\SchematicCreateTask;
use czechpmdevs\buildertools\async\schematics\SchematicLoadTask;
use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Canceller;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\format\BuilderToolsSchematic;
use czechpmdevs\buildertools\schematics\format\MCEditSchematic;
use czechpmdevs\buildertools\schematics\format\MCStructureSchematic;
use czechpmdevs\buildertools\schematics\format\Schematic;
use czechpmdevs\buildertools\schematics\format\SpongeSchematic;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function array_keys;
use function array_map;
use function array_unique;
use function basename;
use function file_exists;
use function in_array;
use function microtime;
use function pathinfo;
use function strtolower;
use function touch;
use function trim;
use function unserialize;
use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

class SchematicsManager {

	/** @phpstan-var array<class-string<Schematic>> */
	private static array $registeredTypes = [];
	/** @var BlockArray[] */
	private static array $loadedSchematics = [];

	public static function lazyInit(): void {
		if(!empty(SchematicsManager::$registeredTypes)) {
			return;
		}

		SchematicsManager::$registeredTypes[] = BuilderToolsSchematic::class;
		SchematicsManager::$registeredTypes[] = MCEditSchematic::class;
		SchematicsManager::$registeredTypes[] = MCStructureSchematic::class;
		SchematicsManager::$registeredTypes[] = SpongeSchematic::class;
	}

	/**
	 * @phpstan-param Closure(SchematicActionResult $result): void $callback
	 */
	public static function loadSchematic(string $schematic, Closure $callback): void {
		$startTime = microtime(true);

		$file = $schematic;
		if(!SchematicsManager::findSchematicFile($file)) {
			$callback(SchematicActionResult::error("Could not find file for $schematic"));
			return;
		}

		/** @phpstan-ignore-next-line */
		AsyncQueue::submitTask(new SchematicLoadTask($file), function(SchematicLoadTask $task) use ($startTime, $schematic, $callback): void {
			if($task->error !== null) {
				$callback(SchematicActionResult::error($task->error));
				return;
			}

			$blockArray = unserialize($task->blockArray);
			if(!$blockArray instanceof BlockArray) {
				$callback(SchematicActionResult::error("Error whilst reading object from another thread."));
				return;
			}

			SchematicsManager::$loadedSchematics[$task->name] = $blockArray;
			$callback(SchematicActionResult::success(microtime(true) - $startTime));
		});
	}

	public static function unloadSchematic(string $schematic): bool {
		if(!isset(SchematicsManager::$loadedSchematics[$schematic])) {
			return false;
		}

		unset(SchematicsManager::$loadedSchematics[$schematic]);
		return true;
	}

	/**
	 * @return string[]
	 */
	public static function getLoadedSchematics(): array {
		return array_keys(SchematicsManager::$loadedSchematics);
	}

	/**
	 * @phpstan-param Closure(SchematicActionResult $result): void $callback
	 */
	public static function createSchematic(Player $player, Vector3 $pos1, Vector3 $pos2, string $schematicName, Closure $callback): void {
		$startTime = microtime(true);

		$format = SchematicsManager::getSchematicByExtension(BuilderTools::getConfiguration()->getStringProperty("output-schematics-format"));
		BuilderTools::getInstance()->getLogger()->debug("Using $format format to create schematic...");

		/** @noinspection ALL */
		$targetFile = BuilderTools::getInstance()->getDataFolder() . "schematics" . DIRECTORY_SEPARATOR . basename($schematicName, ".schematic") . "." . $format::getFileExtension();
		if(!@touch($targetFile)) {
			$callback(SchematicActionResult::error("Could not access target file"));
			return;
		}

		$fillSession = new FillSession($player->getWorld());
		$blocks = new BlockArray();

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		for($y = $minY; $y <= $maxY; ++$y) {
			for($x = $minX; $x <= $maxX; ++$x) {
				for($z = $minZ; $z <= $maxZ; ++$z) {
					$fillSession->getBlockAt($x, $y, $z, $id, $meta);
					$blocks->addBlockAt($x - $minX, $y - $minY, $z - $minZ, $id, $meta);
				}
			}
		}

		/** @phpstan-ignore-next-line */
		AsyncQueue::submitTask(new SchematicCreateTask($targetFile, $format, $blocks), function(SchematicCreateTask $task) use ($callback, $startTime): void {
			if($task->error !== null) {
				$callback(SchematicActionResult::error($task->error));
				return;
			}

			$callback(SchematicActionResult::success(microtime(true) - $startTime));
		});
	}

	public static function pasteSchematic(Player $player, string $schematicName): EditorResult {
		$startTime = microtime(true);

		if(!isset(SchematicsManager::$loadedSchematics[$schematicName])) {
			return EditorResult::error("Schematic $schematicName is not loaded.");
		}

		$schematic = clone SchematicsManager::$loadedSchematics[$schematicName];

		$fillSession = new FillSession($player->getWorld(), true, true);

		$floorX = $player->getPosition()->getFloorX();
		$floorY = $player->getPosition()->getFloorY();
		$floorZ = $player->getPosition()->getFloorZ();

		while($schematic->hasNext()) {
			$schematic->readNext($x, $y, $z, $id, $meta);
			if($id != 0)
				$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $id, $meta);
		}

		if($fillSession->getBlocksChanged() == 0) {
			return EditorResult::error("0 blocks changed");
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	private static function findSchematicFile(string &$file): bool {
		$dataFolder = BuilderTools::getInstance()->getDataFolder() . "schematics" . DIRECTORY_SEPARATOR;
		$allowedExtensions = array_unique(array_map(fn(string $schematic) => $schematic::getFileExtension(), SchematicsManager::$registeredTypes));
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if(in_array($ext, $allowedExtensions, true)) {
			if(file_exists($dataFolder . $file)) {
				$file = $dataFolder . $file;
				return true;
			}
		}

		foreach($allowedExtensions as $extension) {
			if(file_exists($dataFolder . $file . "." . $extension)) {
				$file = $dataFolder . $file . "." . $extension;
				return true;
			}
		}

		return false;
	}

	/**
	 * @return class-string<Schematic>|null
	 */
	public static function getSchematicFormat(string $rawData): ?string {
		foreach(SchematicsManager::$registeredTypes as $class) {
			if($class::validate($rawData)) {
				return $class;
			}
		}

		return null;
	}

	/**
	 * @return class-string<Schematic>
	 */
	public static function getSchematicByExtension(string $extension): string {
		$extension = trim(strtolower($extension));

		foreach(SchematicsManager::$registeredTypes as $class) {
			if(strtolower($class::getFileExtension()) == $extension) {
				return $class;
			}
		}

		BuilderTools::getInstance()->getLogger()->warning("Invalid default schematic format set in config.yml! Using MCEdit...");
		return MCEditSchematic::class;
	}
}