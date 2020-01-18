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

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockList
 * @package buildertools\editors\object
 */
class BlockList implements BlockStorage {

    /** @var Block[] $blocks */
    private $blocks = [];

    /** @var Level $level */
    private $level;

    /** @var BlockListMetadata $metadata */
    private $metadata;

    /**
     * @param Vector3 $pos
     * @param Block $block
     */
    public function addBlock(Vector3 $pos, Block $block): void {
        $block = clone $block;
        $block->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
        $this->blocks[] = $block;
    }

    /**
     * @param Block[] $blocks
     */
    public function setAll(array $blocks): void {
        $this->blocks = $blocks;
    }

    /**
     * @return Block[] $blocks
     */
    public function getAll(): array {
        return $this->blocks;
    }

    /**
     * @param Level|null $level
     */
    public function setLevel(?Level $level): void {
        $this->level = $level;
    }

    /**
     * @return Level|null
     */
    public function getLevel(): ?Level {
        return $this->level;
    }

    /**
     * @param bool $build
     *
     * @return BlockListMetadata|null
     */
    public function getMetadata(bool $build = true): ?BlockListMetadata {
        if(is_null($this->metadata) && $build) {
            $this->metadata = new BlockListMetadata($this);
        }

        return $this->metadata;
    }

    /**
     * @param int|Vector3 $x
     * @param int|null $y
     * @param int|null $z
     *
     * @return BlockList
     */
    public function add($x = 0, $y = 0, $z = 0) {
        $blockList = clone $this;

        /** @var Vector3 $vec */
        $vec = null;
        if($x instanceof Vector3) {
            $vec = $x;
        } else {
            $vec = new Vector3($x, $y, $z);
        }

        foreach ($blockList->getAll() as $block) {
            $block->setComponents($block->getX()+$vec->getX(), $block->getY()+$vec->getY(), $block->getZ()+$vec->getZ());
        }

        return $blockList;
    }

    /**
     * @param int|Vector3 $x
     * @param int|null $y
     * @param int|null $z
     *
     * @return BlockList
     */
    public function subtract($x = 0, $y = 0, $z = 0) {
        /** @var Vector3 $vec */
        $vec = null;
        if($x instanceof Vector3) {
            $vec = $x;
        } else {
            $vec = new Vector3($x, $y, $z);
        }

        return $this->add($vec->multiply(-1));
    }

    /**
     * @param Level $level
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     *
     * @return BlockList
     */
    public static function build(Level $level, Vector3 $pos1, Vector3 $pos2): BlockList {
        $blockList = new BlockList();
        $blockList->setLevel($level);

        for($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); $x++) {
            for($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); $y++) {
                for($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); $z++) {
                    $blockList->addBlock($v = new Vector3($x, $y, $z), $level->getBlock($v));
                }
            }
        }

        return $blockList;
    }
}