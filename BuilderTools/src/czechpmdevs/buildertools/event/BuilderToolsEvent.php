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

namespace czechpmdevs\buildertools\event;

use buildertools\BuilderTools;
use pocketmine\event\plugin\PluginEvent;

/**
 * Class BuilderToolsEvent
 * @package buildertools\event
 */
abstract class BuilderToolsEvent extends PluginEvent {

    /** @var null $handlerList */
    public static $handlerList = \null;

    /** @var array $settings */
    protected $settings;

    /**
     * BuilderToolsEvent constructor.
     */
    public function __construct(array $settings) {
        $this->settings = $settings;
        parent::__construct(BuilderTools::getInstance());
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings) {
        $this->settings = $settings;
    }

    /**
     * @return array $settings
     */
    public function getSettings(): array {
        return $this->settings;
    }
}
