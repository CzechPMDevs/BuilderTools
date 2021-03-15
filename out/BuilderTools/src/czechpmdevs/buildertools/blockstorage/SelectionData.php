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

use InvalidArgumentException;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SelectionData extends BlockArray {

    /** @var Player */
    protected $player;
    /** @var Vector3|null */
    protected $playerPosition = null;

    /**
     * @param bool $modifyBuffer If it's false, only relative position will be changed.
     */
    public function addVector3(Vector3 $vector3, bool $modifyBuffer = false): BlockArray {
        if(!$vector3->ceil()->equals($vector3)) {
            throw new InvalidArgumentException("Vector3 coordinates must be integer.");
        }

        if($this->playerPosition instanceof Vector3) {
            $clipboard = clone $this;
            $clipboard->playerPosition->add($vector3);

            return $clipboard;
        }

        return parent::addVector3($vector3);
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    /**
     * @return $this
     */
    public function setPlayer(Player $player): SelectionData {
        $this->player = $player;

        return $this;
    }

    public function getPlayerPosition(): ?Vector3 {
        return $this->playerPosition;
    }

    /**
     * @return $this
     */
    public function setPlayerPosition(?Vector3 $playerPosition): SelectionData {
        $this->playerPosition = $playerPosition === null ? null : $playerPosition->ceil();

        return $this;
    }
}