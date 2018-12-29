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

namespace czechpmdevs\buildertools\schematics;

use czechpmdevs\buildertools\BuilderTools;

/**
 * Class SchematicsManager
 * @package czechpmdevs\buildertools\schematics
 */
class SchematicsManager {

    /** @var BuilderTools $plugin */
    protected $plugin;

    /** @var Schematic[] $schematics */
    public $schematics = [];

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
        foreach (glob($this->plugin->getDataFolder() . "schematics/*.schematic") as $file) {
            $this->schematics[basename($file, ".schematic")] = new Schematic($file);
        }
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
        return $this->schematics;
    }
}