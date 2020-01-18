<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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
use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\editors\Filler;
use pocketmine\Player;

/**
 * Class SchematicsManager
 * @package czechpmdevs\buildertools\schematics
 */
class SchematicsManager {

    /** @var BuilderTools $plugin */
    protected $plugin;

    /** @var Schematic[] $schematics */
    public $schematics = [];

    /** @var Schematic[] $players */
    public $players;

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
        if(!file_exists($this->plugin->getDataFolder() . "schematics")) {
            @mkdir($this->plugin->getDataFolder() . "schematics");
        }
        if(!file_exists($this->plugin->getDataFolder() . "schematics")) {
            @mkdir($this->plugin->getDataFolder() . "schematics");
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

        $fillList = new BlockList();
        $fillList->setLevel($player->getLevel());
        $debugged = false;
        $fillList->add($player);

        foreach ($blockList->getAll() as $block) {
            if($block->getId() != 0 && !$debugged) {
                var_dump($block);
                $debugged = true;
            }
        }

        /** @var Filler $filler */
        $filler = new Filler();
        $filler->fill($player, $fillList);
        $player->sendMessage(BuilderTools::getPrefix() . "Schematic successfully pasted.");
        return true;
    }

    /**
     * @param string $name
     * @return Schematic|null
     */
    public function getSchematic(string $name): ?Schematic {
        return isset($this->schematics[$name]) ? $this->schematics[$name] : null;
    }

    /**
     * @return Schematic[] $schematics
     */
    public function getLoadedSchematics(): array {
        /** @var Schematic $value */
        return array_filter($this->getAllSchematics(), function ($value) {
            return $value->isLoaded;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return Schematic[] $schematics
     */
    public function getAllSchematics(): array {
        return $this->schematics;
    }
}