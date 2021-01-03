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

namespace czechpmdevs\asyncfill\storage;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\math\Vector3;

/**
 * Class Block
 * @package czechpmdevs\asyncfill\storage
 */
class ThreadSafeBlock {

    /** @var int $id */
    private int $id;
    /** @var int $damage */
    private int $damage;

    /**
     * @var int $x
     * @var int $y
     * @var int $z
     */
    private int $x = 0, $y = 0, $z = 0;

    /**
     * Block constructor.
     * @param int $id
     * @param int $damage
     */
    public function __construct(int $id = BlockIds::AIR, int $damage = 0) {
        $this->id = $id;
        $this->damage = $damage;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $damage
     */
    public function setDamage(int $damage): void {
        $this->damage = $damage;
    }

    /**
     * @return int
     */
    public function getDamage(): int {
        return $this->damage;
    }

    /**
     * @return int
     */
    public function getX(): int {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getZ(): int {
        return $this->z;
    }

    /**
     * @return Vector3
     */
    public function asVector3(): Vector3 {
        return new Vector3($this->x, $this->y, $this->z);
    }

    /**
     * @param Vector3 $vector3
     * @return $this
     */
    public function setComponents(Vector3 $vector3): self {
        $this->x = $vector3->getX();
        $this->y = $vector3->getY();
        $this->z = $vector3->getZ();

        return $this;
    }

    /**
     * @param bool $addPosition
     * @return Block
     */
    public function getBlock(bool $addPosition = false): Block {
        $block = Block::get($this->getId(), $this->getDamage());
        if($addPosition) {
            $block->setComponents($this->x, $this->y, $this->z);
        }

        return $block;
    }
}