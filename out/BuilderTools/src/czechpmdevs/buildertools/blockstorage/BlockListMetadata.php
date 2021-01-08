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

/**
 * Class BlockListMetadata
 * @package czechpmdevs\buildertools\editors\object
 *
 * @deprecated
 */
class BlockListMetadata {

    /** @var BlockList $blockList */
    private $blockList;

    /**
     * @var int|null $maxX
     * @var int|null $maxY
     * @var int|null $maxZ
     */
    public $maxX = null, $maxY = null, $maxZ = null;

    /**
     * @var int|null $minX
     * @var int|null $minY
     * @var int|null $minZ
     */
    public $minX = null, $minY = null, $minZ = null;

    /**
     * BlockListMetadata constructor.
     * @param BlockList $blockList
     */
    public function __construct(BlockList $blockList) {
        $this->blockList = $blockList;
        if($this->blockList instanceof BlockMap) {
            $this->calculateMapMetadata();
        } else {
            $this->calculateMetadata();
        }
    }

    private function calculateMapMetadata() {
        /** @var BlockMap $map */
        $map = $this->blockList;

        $values = array_keys($map->getBlockMap());
        $this->minX = min($values);
        $this->maxX = max($values);

        $values = array_merge(...array_map('array_keys', $map->getBlockMap()));
        $this->minY = min($values);
        $this->maxY = max($values);

        $values = array_merge(...array_merge(...array_map(function ($value) {
            return array_map("array_keys", $value);
        }, $map->getBlockMap())));
        $this->minZ = min($values);
        $this->maxZ = max($values);
    }

    private function calculateMetadata() {
        foreach ($this->blockList->getAll() as $block) {
            if(is_null($this->maxX) || $this->maxX < $block->getX()) {
                $this->maxX = $block->getX();
            }
            if(is_null($this->maxY) || $this->maxY < $block->getY()) {
                $this->maxY = $block->getY();
            }
            if(is_null($this->maxZ) || $this->maxZ < $block->getZ()) {
                $this->maxZ = $block->getZ();
            }

            if(is_null($this->minX) || $this->minX > $block->getX()) {
                $this->minX = $block->getX();
            }
            if(is_null($this->minY) || $this->minY > $block->getY()) {
                $this->minY = $block->getY();
            }
            if(is_null($this->minZ) || $this->minZ > $block->getZ()) {
                $this->minZ = $block->getZ();
            }
        }
    }

    public function recalculateMetadata() {
        $this->calculateMetadata();
    }

    public function getMinimum(): Vector3 {
        return new Vector3($this->minX, $this->minY, $this->minZ);
    }

    public function getMaximum(): Vector3 {
        return new Vector3($this->maxX, $this->maxY, $this->maxZ);
    }
}