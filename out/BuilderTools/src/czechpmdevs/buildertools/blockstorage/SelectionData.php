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

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\math\Vector3;
use pocketmine\Player;

class SelectionData extends BlockArray {

    /** @var Player */
    protected $player;
    /** @var Vector3 */
    protected $playerPosition;

    public function getPlayer(): Player {
        return $this->player;
    }

    public function setPlayer(Player $player) {
        $this->player = $player;

        return $this;
    }

    public function getPlayerPosition(): Vector3 {
        return $this->playerPosition;
    }

    public function setPlayerPosition(Vector3 $playerPosition) {
        $this->playerPosition = $playerPosition;

        return $this;
    }
}