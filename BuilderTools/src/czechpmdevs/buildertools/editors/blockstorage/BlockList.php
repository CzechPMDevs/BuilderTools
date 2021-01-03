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

namespace czechpmdevs\buildertools\editors\blockstorage;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockList
 * @package buildertools\editors\object
 */
class BlockList implements BlockStorage {

    public const BUILD_XYZ = 0;
    public const BUILD_YZX = 1;

    /** @var Block[] $blocks */
    private array $blocks = [];
    /** @var Level|null $level */
    private ?Level $level;
    /** @var BlockListMetadata|null $metadata */
    private ?BlockListMetadata $metadata;

    public function addBlock(Vector3 $position, Block $block): self {
        $block = Block::get($block->getId(), $block->getDamage());
        $block->setComponents($position->getX(), $position->getY(), $position->getZ());
        $this->blocks[] = $block;

        return $this;
    }

    public function setAll(array $blocks): self {
        $this->blocks = $blocks;

        return $this;
    }

    public function getAll(): array {
        return $this->blocks;
    }

    public function setLevel(?Level $level): self {
        $this->level = $level;

        return $this;
    }

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
    public function add($x = 0, $y = 0, $z = 0): BlockList {
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
    public function subtract($x = 0, $y = 0, $z = 0): BlockList {
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
     * @param int $algorithm
     * @return BlockList
     */
    public static function build(Level $level, Vector3 $pos1, Vector3 $pos2, int $algorithm = BlockList::BUILD_XYZ): BlockList {
        $blockList = new BlockList();
        $blockList->setLevel($level);

        if($algorithm == BlockList::BUILD_XYZ) {
            for ($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); $x++) {
                for ($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); $y++) {
                    for ($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); $z++) {
                        $blockList->addBlock($v = new Vector3($x, $y, $z), $level->getBlock($v));
                    }
                }
            }
        } else if($algorithm == BlockList::BUILD_YZX) {
            for($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); $y++) {
                for ($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); $z++) {
                    for ($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); $x++) {
                        $blockList->addBlock($v = new Vector3($x, $y, $z), $level->getBlock($v));
                    }
                }
            }
        }


        return $blockList;
    }
}