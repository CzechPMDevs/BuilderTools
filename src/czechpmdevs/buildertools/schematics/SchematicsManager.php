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

namespace czechpmdevs\buildertools\schematics;

use Closure;
use czechpmdevs\buildertools\async\AsyncQueue;
use czechpmdevs\buildertools\async\schematics\SchematicCreateTask;
use czechpmdevs\buildertools\async\schematics\SchematicLoadTask;
use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\format\BuilderToolsSchematic;
use czechpmdevs\buildertools\schematics\format\MCEditSchematic;
use czechpmdevs\buildertools\schematics\format\MCStructureSchematic;
use czechpmdevs\buildertools\schematics\format\Schematic;
use czechpmdevs\buildertools\schematics\format\SpongeSchematic;
use czechpmdevs\buildertools\session\SessionManager;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function array_keys;
use function array_map;
use function array_unique;
use function basename;
use function file_exists;
use function in_array;
use function pathinfo;
use function strtolower;
use function touch;
use function trim;
use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

class SchematicsManager {

	/** @var class-string<Schematic>[] */
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
		$timer = new Timer();

		$file = $schematic;
		if(!SchematicsManager::findSchematicFile($file)) {
			$callback(SchematicActionResult::error("Could not find file for $schematic"));
			return;
		}

		/** @phpstan-ignore-next-line */
		AsyncQueue::submitTask(new SchematicLoadTask($file), function(SchematicLoadTask $task) use ($timer, $schematic, $callback): void {
			if($task->getErrorMessage() !== null) {
				$callback(SchematicActionResult::error($task->getErrorMessage()));
				return;
			}

			SchematicsManager::$loadedSchematics[$task->name] = $task->blockStorage->asBlockArray();
			$callback(SchematicActionResult::success($timer->time()));
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
		$timer = new Timer();

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
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$blocks->addBlockAt($x - $minX, $y - $minY, $z - $minZ, $fullBlockId);
				}
			}
		}

		/** @phpstan-ignore-next-line */
		AsyncQueue::submitTask(new SchematicCreateTask($targetFile, $format, $blocks), function(SchematicCreateTask $task) use ($timer, $callback): void {
			if($task->error !== null) {
				$callback(SchematicActionResult::error($task->error));
				return;
			}

			$callback(SchematicActionResult::success($timer->time()));
		});
	}

	public static function pasteSchematic(Player $player, string $schematicName): UpdateResult {
		$timer = new Timer();

		if(!isset(SchematicsManager::$loadedSchematics[$schematicName])) {
			return UpdateResult::error("Schematic $schematicName is not loaded.");
		}

		$schematic = SchematicsManager::$loadedSchematics[$schematicName];

		$fillSession = new FillSession($player->getWorld(), true, true);

		$floorX = $player->getPosition()->getFloorX();
		$floorY = $player->getPosition()->getFloorY();
		$floorZ = $player->getPosition()->getFloorZ();

		$iterator = new BlockArrayIteratorHelper($schematic);
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);
			if($fullBlockId !== 0) $fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);
		}

		if($fillSession->getBlocksChanged() === 0) {
			return UpdateResult::error("0 blocks changed");
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($fillSession->getChanges(), $player->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
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
			if(strtolower($class::getFileExtension()) === $extension) {
				return $class;
			}
		}

		BuilderTools::getInstance()->getLogger()->warning("Invalid default schematic format set in config.yml! Using MCEdit...");
		return MCEditSchematic::class;
	}
}