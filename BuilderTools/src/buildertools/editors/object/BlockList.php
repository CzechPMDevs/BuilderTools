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

namespace buildertools\editors\object;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockList
 * @package buildertools\editors\object
 */
class BlockList {

    /** @var Block[] $blocks */
    private $blocks = [];

    /** @var Level $level */
    private $level;

    /**
     * @param Vector3 $pos
     * @param Block $block
     */
    public function addBlock(Vector3 $pos, Block $block) {
        $block = clone $block;
        $block->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
        $this->blocks[] = $block;
    }

    /**
     * @param Level $level
     */
    public function setLevel(Level $level) {
        $this->level = $level;
    }

    /**
     * @return Level $level
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return Block[] $blocks
     */
    public function getAll() {
        return $this->blocks;
    }

    /**
     * @param Block[] $blocks
     */
    public function setAll(array $blocks) {
        $this->blocks = $blocks;
    }
}