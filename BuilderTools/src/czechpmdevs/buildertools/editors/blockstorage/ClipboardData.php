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

namespace czechpmdevs\buildertools\editors\blockstorage;

use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class ClipboardData
 * @package czechpmdevs\buildertools\editors\blockstorage
 */
class ClipboardData extends BlockList {

    /** @var Player $player */
    private $player;

    /** @var Vector3 $playerPosition */
    private $playerPosition;

    /**
     * ClipboardData constructor.
     * @param Player $player
     */
    public function __construct(Player $player) {
        $this->player = $player;
    }

    /**
     * @param Vector3 $playerPosition
     */
    public function setPlayerPosition(Vector3 $playerPosition): void {
        $this->playerPosition = $playerPosition;
    }

    /**
     * @return Vector3
     */
    public function getPlayerPosition(): Vector3 {
        return $this->playerPosition;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player {
        return $this->player;
    }
}