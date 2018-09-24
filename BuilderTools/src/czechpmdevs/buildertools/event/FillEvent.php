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

use pocketmine\event\Cancellable;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class FillEvent extends BuilderToolsEvent implements Cancellable {

    /** @var Player $player */
    protected $player;

    /** @var Level $level */
    protected $level;

    /**
     * @var Vector3 $pos1
     * @var Vector3 $pos2
     */
    protected $pos1, $pos2;

    /**
     * FillEvent constructor.
     * @param Player $player
     * @param Level $level
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param array $settings
     */
    public function __construct(Player $player, Level $level, Vector3 $pos1, Vector3 $pos2, array $settings) {
        $this->player = $player;
        $this->level = $level;
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
        parent::__construct($settings);
    }

    /**
     * @return Player $player
     */
    public function getPlayer(): Player {
        return $this->player;
    }

    /**
     * @return Level $level
     */
    public function getLevel(): Level {
        return $this->level;
    }

    /**
     * @return Vector3 $pos1
     */
    public function getPos1(): Vector3 {
        return $this->pos1;
    }

    /**
     * @return Vector3 $pos2
     */
    public function getPos2(): Vector3 {
        return $this->pos2;
    }
}
