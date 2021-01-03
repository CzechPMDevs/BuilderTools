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

use czechpmdevs\buildertools\async\SchematicLoadTask;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Filler;
use pocketmine\Player;

/**
 * Class SchematicsManager
 * @package czechpmdevs\buildertools\schematics
 */
class SchematicsManager {

    /** @var BuilderTools $plugin */
    protected BuilderTools $plugin;

    /** @var Schematic[] $schematics */
    public array $schematics = [];
    /** @var Schematic[] $players */
    public array $players;

    /**
     * SchematicsManager constructor.
     * @param BuilderTools $plugin
     */
    public function __construct(BuilderTools $plugin) {
        $this->plugin = $plugin;
        $this->init();
        $this->loadSchematics();
    }

    public function init() {
        if(!file_exists($this->getPlugin()->getDataFolder() . "schematics")) {
            @mkdir($this->getPlugin()->getDataFolder() . "schematics");
        }
        if(!file_exists($this->getPlugin()->getDataFolder() . "schematics")) {
            @mkdir($this->getPlugin()->getDataFolder() . "schematics");
        }
    }

    public function loadSchematics() {
        $this->schematics = [];
        //$unloaded = BuilderTools::getConfiguration()["schematics"]["load"] != "startup";
        foreach (glob($this->plugin->getDataFolder() . "schematics/*.schematic") as $file) {
            $this->loadSchematic($file);
        }
    }

    /**
     * @param string $file
     * @param Schematic $schematic
     */
    public function registerSchematic(string $file, Schematic $schematic) {
        $schematic->file = $file;
        $this->schematics[basename($file, ".schematic")] = $schematic;
    }

    /**
     * @param string $path
     */
    public function loadSchematic(string $path) {
        $this->plugin->getLogger()->info("Loading schematic from $path...");
        $this->plugin->getServer()->getAsyncPool()->submitTask(new SchematicLoadTask($path));
    }

    /**
     * @param Player $player
     * @param Schematic $schematic
     */
    public function addToPaste(Player $player, Schematic $schematic) {
        $this->players[$player->getName()] = $schematic;
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function pasteSchematic(Player $player): bool {
        if(!isset($this->players[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix(). "§cType //schem load <filename> to load schematic first!");
            return false;
        }

        $schematic = $this->players[$player->getName()];
        $blockList = $schematic->getBlockList();

        if($blockList === null) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cInvalid schematic format (Sponge) isn't supported.");
            return false;
        }

        $blockList->setLevel($player->getLevel());
        $blockList = $blockList->add($player);

        $filler = new Filler;
        $filler->fill($player, $blockList);
        $player->sendMessage(BuilderTools::getPrefix() . "Schematic successfully pasted.");
        return true;
    }

    /**
     * @param string $name
     * @return Schematic|null
     */
    public function getSchematic(string $name): ?Schematic {
        return $this->schematics[$name] ?? null;
    }

    /**
     * @return Schematic[] $schematics
     */
    public function getLoadedSchematics(): array {
        return array_filter($this->getAllSchematics(), function (Schematic $value) {
            return $value->isLoaded;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return Schematic[] $schematics
     */
    public function getAllSchematics(): array {
        return $this->schematics;
    }

    /**
     * @return BuilderTools
     */
    public function getPlugin(): BuilderTools {
        return $this->plugin;
    }
}