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

use czechpmdevs\buildertools\async\schematics\MCEditLoadTask;
use czechpmdevs\buildertools\async\schematics\MCEditSaveTask;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Filler;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\format\MCEditSchematics;
use czechpmdevs\buildertools\Selectors;
use Exception;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use function basename;
use function file_get_contents;
use function glob;
use function mkdir;

class SchematicsManager {

    public const SCHEMATIC_MCEDIT_FORMAT = 0x00;
    public const SCHEMATIC_UNKNOWN_FORMAT = 0x01;

    /** @var BuilderTools */
    protected BuilderTools $plugin;

    /** @var SchematicData[] */
    public array $schematics = [];
    /** @var SchematicData[] */
    public array $players;

    public function __construct(BuilderTools $plugin) {
        $this->plugin = $plugin;
        $this->init();
        $this->loadSchematics();
    }

    public function init(): void {
        if(!file_exists($this->getPlugin()->getDataFolder() . "schematics")) {
            @mkdir($this->getPlugin()->getDataFolder() . "schematics");
        }
    }

    public function loadSchematics(): void {
        $this->schematics = [];

        $files = glob($this->plugin->getDataFolder() . "schematics/*.schematic");
        if(!$files) {
            BuilderTools::getInstance()->getLogger()->error("Could not read schematics folder.");
            return;
        }

        foreach ($files as $file) {
            $this->loadSchematic($file);
        }
    }

    public function registerSchematic(string $file, SchematicData $schematic): void {
        $this->schematics[basename($file, ".schematic")] = $schematic;
    }

    public function loadSchematic(string $path): void {
        $this->plugin->getLogger()->info("Loading schematic from $path...");
        switch ($this->getSchematicFormat($path)) {
            case self::SCHEMATIC_MCEDIT_FORMAT:
                $this->plugin->getServer()->getAsyncPool()->submitTask(new MCEditLoadTask($path));
                break;
            case self::SCHEMATIC_UNKNOWN_FORMAT:
                $this->plugin->getLogger()->error("Unrecognised schematics format for file $path");
                break;
        }
    }

    private function getSchematicFormat(string $path): int {
        try {
            $file = file_get_contents($path);
            if(!$file) {
                BuilderTools::getInstance()->getLogger()->error("Could not read $path");
                return self::SCHEMATIC_UNKNOWN_FORMAT;
            }

            /** @var CompoundTag $data */
            $data = (new BigEndianNBTStream())->readCompressed($file);
            if($data->offsetExists("Blocks") && $data->offsetExists("Data")) {
                return self::SCHEMATIC_MCEDIT_FORMAT;
            }
        }
        catch (Exception $ignore) {}

        return self::SCHEMATIC_UNKNOWN_FORMAT;
    }

    public function addToPaste(Player $player, SchematicData $schematic): void {
        $this->players[$player->getName()] = $schematic;
    }

    public function pasteSchematic(Player $player): bool {
        if(!isset($this->players[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix(). "§cType //schem load <filename> to load schematic first!");
            return false;
        }

        $schematic = $this->players[$player->getName()]->addVector3($player);
        $schematic->setLevel($player->getLevel());

        Filler::getInstance()->fill($player, $schematic);

        $player->sendMessage(BuilderTools::getPrefix() . "Schematic successfully pasted.");
        return true;
    }

    public function createSchematic(Player $player, string $file): void {
        /** @var Vector3 $pos1 */
        $pos1 = Selectors::getPosition($player, 1);
        /** @var Vector3 $pos2 */
        $pos2 = Selectors::getPosition($player, 2);

        $schematic = new MCEditSchematics();
        $schematic->setFile($file);
        $schematic->setAxisVector(Math::calculateAxisVec($pos1, $pos2));

        $this->getPlugin()->getServer()->getAsyncPool()->submitTask(new MCEditSaveTask($schematic));
    }

    public function getSchematic(string $name): ?SchematicData {
        return $this->schematics[$name] ?? null;
    }

    /**
     * @return SchematicData[]
     */
    public function getAllSchematics(): array {
        return $this->schematics;
    }

    public function getPlugin(): BuilderTools {
        return $this->plugin;
    }
}