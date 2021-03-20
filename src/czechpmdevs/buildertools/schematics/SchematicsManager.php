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
use czechpmdevs\buildertools\schematics\format\MCEditSchematic;
use Error;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;
use function array_keys;
use function basename;
use function file_exists;
use function microtime;
use function pathinfo;
use function touch;
use function unserialize;
use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

class SchematicsManager {

    /** @var BlockArray[] */
    private static array $loadedSchematics = [];

    /**
     * @phpstan-param Closure(SchematicActionResult $result): void $callback
     */
    public static function loadSchematic(string $schematic, Closure $callback): void {
        $startTime = microtime(true);

        $file = $schematic;
        if(!self::findSchematicFile($file)) {
            $callback(SchematicActionResult::error("Could not find file for $schematic"));
            return;
        }

        /** @phpstan-ignore-next-line */
        AsyncQueue::submitTask(new SchematicLoadTask($file), function (SchematicLoadTask $task) use ($startTime, $schematic, $callback): void {
            if($task->error !== null) {
                $callback(SchematicActionResult::error($task->error));
                return;
            }

            $blockArray = unserialize($task->blockArray);

            if(!$blockArray instanceof BlockArray) {
                $callback(SchematicActionResult::error("Error whilst reading object from another thread."));
            }

            self::$loadedSchematics[basename($schematic, ".schematic")] = $blockArray;

            $callback(SchematicActionResult::success(microtime(true) - $startTime));
        });
    }

    public static function unloadSchematic(string $schematic): bool {
        $schematic = basename($schematic, ".schematic");
        if(!isset(self::$loadedSchematics[$schematic])) {
            return false;
        }

        unset(self::$loadedSchematics[$schematic]);
        return true;
    }

    /**
     * @return string[]
     */
    public static function getLoadedSchematics(): array {
        return array_keys(self::$loadedSchematics);
    }

    /**
     * @phpstan-param Closure(SchematicActionResult $result): void $callback
     */
    public static function createSchematic(Player $player, Vector3 $pos1, Vector3 $pos2, string $schematicName, Closure $callback): void {
        $startTime = microtime(true);

        $targetFile = BuilderTools::getInstance()->getDataFolder() . "schematics" . DIRECTORY_SEPARATOR . basename($schematicName, ".schematic") . ".schematic";
        if(!@touch($targetFile)) {
            $callback(SchematicActionResult::error("Could not access target file"));
            return;
        }

        $fillSession = new FillSession($player->getLevelNonNull());
        $blocks = new BlockArray();

        Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

        $floorX = $player->getFloorX();
        $floorY = $player->getFloorY();
        $floorZ = $player->getFloorZ();

        for($y = $minY; $y <= $maxY; ++$y) {
            for($x = $minX; $x <= $maxX; ++$x) {
                for($z = $minZ; $z <= $maxZ; ++$z) {
                    $fillSession->getBlockAt($x, $y, $z, $id, $meta);
                    $blocks->addBlockAt($x - $floorX, $y - $floorY, $z - $floorZ, $id, $meta);
                }
            }
        }

        /** @phpstan-ignore-next-line */
        AsyncQueue::submitTask(new SchematicCreateTask($targetFile, $blocks), function (SchematicCreateTask $task) use ($startTime): void {
            if($task->error !== null) {
                SchematicActionResult::error($task->error);
                return;
            }

            SchematicActionResult::success(microtime(true) - $startTime);
        });
    }

    public static function pasteSchematic(Player $player, string $schematicName): EditorResult {
        $startTime = microtime(true);

        $schematicName = basename($schematicName, ".schematic");
        if(!isset(self::$loadedSchematics[$schematicName])) {
            return EditorResult::error("Schematic $schematicName is not loaded.");
        }

        $schematic = clone self::$loadedSchematics[$schematicName];

        $fillSession = new FillSession($player->getLevelNonNull(), true, true);

        $floorX = $player->getFloorX();
        $floorY = $player->getFloorY();
        $floorZ = $player->getFloorZ();

        while ($schematic->hasNext()) {
            $schematic->readNext($x, $y, $z, $id, $meta);
            if($id != 0)
                $fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $id, $meta);
        }

        $fillSession->reloadChunks($player->getLevelNonNull());

        /** @phpstan-var BlockArray $changes */
        $changes = $fillSession->getChanges();
        Canceller::getInstance()->addStep($player, $changes);

        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
    }

    private static function findSchematicFile(string &$file): bool {
        $dataFolder = BuilderTools::getInstance()->getDataFolder() . "schematics" . DIRECTORY_SEPARATOR;

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if($ext != "schematic") {
            $file .= ".schematic";
        }

        if(file_exists($dataFolder . $file)) {
            $file = $dataFolder . $file;
            return true;
        }

        return false;
    }

    /**
     * @return string $class
     */
    public static function getSchematicFormat(string $rawData): ?string {
        try {
            $nbt = (new BigEndianNBTStream())->readCompressed($rawData);
            if(!$nbt instanceof CompoundTag) {
                return null;
            }

            if(
                $nbt->hasTag("Width", ShortTag::class) &&
                $nbt->hasTag("Height", ShortTag::class) &&
                $nbt->hasTag("Length", ShortTag::class) &&
                $nbt->hasTag("Blocks", ByteArrayTag::class) &&
                $nbt->hasTag("Data", ByteArrayTag::class)
            ) {
                return MCEditSchematic::class;
            }

            return null;
        } catch (Error $error) {
            return null;
        }
    }
}