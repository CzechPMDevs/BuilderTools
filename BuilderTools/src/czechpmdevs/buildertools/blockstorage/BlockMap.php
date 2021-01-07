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

use Exception;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockMap
 * @package czechpmdevs\buildertools\editors\object
 */
class BlockMap extends BlockList {

    /** @var Block[][][] $blockMap */
    protected array $blockMap = [];

    public function addBlock(Vector3 $position, Block $block): self {
        $this->blockMap[$position->getX()][$position->getY()][$position->getZ()] = $block;

        return $this;
    }

    /**
     * @param Vector3 $vector3
     * @return Block|null
     */
    public function getBlockAt(Vector3 $vector3): ?Block {
        if(isset($this->blockMap[$vector3->getX()])) {
            if(isset($this->blockMap[$vector3->getY()])) {
                if(isset($this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()])) {
                    return $this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()];
                }
                else return null;
            }
            else return null;
        }
        else return null;
    }

    public function setAll(array $blocks): self {
        foreach ($blocks as $block) {
            $this->blockMap[$block->getX()][$block->getY()][$block->getZ()] = $block;
        }

        return $this;
    }

    /**
     * @return Block[] $blocks
     */
    public function getAll(): array {
        $blocks = [];
        foreach ($this->blockMap as $x => $yzb) {
            foreach ($yzb as $y => $zb) {
                foreach ($zb as $z => $block) {
                    $block->setComponents($x, $y, $z);
                    $blocks[] = $block;
                }
            }
        }

        return $blocks;
    }

    public function getFirst(): ?Block{
        /** @var Block|null $block */
        $block = null;
        if(($x = array_key_first($this->blockMap)) !== null) {
            if(($y = array_key_first($this->blockMap[$x])) !== null) {
                if(($z = array_key_first($this->blockMap[$x][$y])) !== null) {
                    $block = array_shift($this->blockMap[$x][$y]);
                    $block->setComponents($x, $y, $z);
                }
                if(empty($this->blockMap[$x][$y])) {
                    unset($this->blockMap[$x][$y]);
                }
            }
            if(empty($this->blockMap[$x])) {
                unset($this->blockMap[$x]);
            }
        }

        return $block;
    }

    public function getLast(): ?Block {
        /** @var Block|null $block */
        $block = null;
        if(($x = array_key_last($this->blockMap)) !== null) {
            if(($y = array_key_last($this->blockMap[$x])) !== null) {
                if(($z = array_key_last($this->blockMap[$x][$y])) !== null) {
                    $block = array_pop($this->blockMap[$x][$y]);
                    $block->setComponents($x, $y, $z);
                }
                if(empty($this->blockMap[$x][$y])) {
                    unset($this->blockMap[$x][$y]);
                }
            }
            if(empty($this->blockMap[$x])) {
                unset($this->blockMap[$x]);
            }
        }

        return $block;
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
     * @param Level $level
     * @param int $x
     * @param int $y
     * @param int $z
     *
     * @return bool
     */
    public function isAirInLevel(Level $level, int $x, int $y, int $z): bool {
        return $this->isVectorInBlockMap(new Vector3($x, $y, $z)) && $this->blockMap[$x][$y][$z]->getId() == 0 && $level->getBlockIdAt($x, $y, $z) == 0;
    }

    /**
     * @param Vector3 $vector3
     *
     * @return bool
     */
    public function isVectorInBlockMap(Vector3 $vector3): bool {
        try {
            return $this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()] instanceof Block;
        }
        catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getBlockMap(): array {
        return $this->blockMap;
    }
}