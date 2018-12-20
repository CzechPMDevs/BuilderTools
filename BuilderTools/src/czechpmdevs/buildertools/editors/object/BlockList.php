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

namespace czechpmdevs\buildertools\editors\object;

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

    /** @var Block[][][] $blockMap */
    private $blockMap = [];

    /** @var Level $level */
    private $level;

    /** @var bool $saveBlockMap */
    private $saveBlockMap = false;

    /**
     * @param Vector3 $pos
     * @param Block $block
     */
    public function addBlock(Vector3 $pos, Block $block) {
        $block = clone $block;
        $block->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
        $this->blocks[] = $block;
        if($this->saveBlockMap) $this->blockMap[$pos->getX()][$pos->getY()][$pos->getZ()] = clone $block;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     *
     * @return bool
     */
    public function isAirAt(int $x, int $y, int $z): bool {
        return $this->isVectorInBlockMap(new Vector3($x, $y, $z)) && $this->blockMap[$x][$y][$z]->getId() == 0;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     *
     * @return bool
     */
    public function isAirInLevel(int $x, int $y, int $z): bool {
        return $this->isVectorInBlockMap(new Vector3($x, $y, $z)) && $this->blockMap[$x][$y][$z]->getId() == 0 && $this->level->getBlockIdAt($x, $y, $z) == 0;
    }

    /**
     * @param Level $level
     */
    public function setLevel(Level $level) {
        $this->level = $level;
    }

    /**
     * @param bool $save
     */
    public function saveBlockMap(bool $save = true) {
        $this->saveBlockMap = $save;
    }

    /**
     * @return \pocketmine\block\Block[][][]
     */
    public function getBlockMap() {
        return $this->blockMap;
    }

    /**
     * @param Vector3 $vector3
     * @return bool
     */
    public function isVectorInBlockMap(Vector3 $vector3): bool {
        if(!isset($this->blocks[$vector3->getX()])) {
            return false;
        }
        if(!isset($this->blocks[$vector3->getX()][$vector3->getY()])) {
            return false;
        }
        if(!isset($this->blocks[$vector3->getX()][$vector3->getY()][$vector3->getZ()])) {
            return false;
        }
        return true;
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

    /**
     * @param array $copyData
     * @param bool $saveBlockMap
     * @return BlockList
     */
    public static function fromCopyData(array $copyData, bool $saveBlockMap = false): BlockList {
        $list = new BlockList;
        $list->saveBlockMap($saveBlockMap);

        /**
         * @var Vector3 $vector3
         * @var Block $block
         */
        foreach ($copyData as [$vector3, $block]) {
            $list->addBlock($vector3, $block);
        }

        return $list;
    }
}