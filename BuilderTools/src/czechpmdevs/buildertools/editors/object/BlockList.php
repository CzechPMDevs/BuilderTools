<?php

/**
 * Copyright (C) 2018-2019  CzechPMDevs
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
use pocketmine\inventory\ArmorInventoryEventProcessor;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockList
 * @package buildertools\editors\object
 */
class BlockList extends BlockMap {

    public const SAVE_TYPE_NORMAL = 0x01;
    public const SAVE_TYPE_BLOCKMAP = 0x02;

    /** @var int $save */
    private $save;

    /** @var Block[] $blocks */
    private $blocks = [];

    /** @var Level $level */
    private $level;

    /** @var Vector3 $playerPosition */
    private $playerPosition = null;

    /** @var BlockListMetadata $metadata */
    private $metadata;

    /**
     * BlockList constructor.
     * @param int $save
     */
    public function __construct(int $save = self::SAVE_TYPE_NORMAL) {
        $this->save = $save;
    }

    /**
     * @param Vector3 $pos
     * @param Block $block
     * @param bool $saveMap
     */
    public function addBlock(Vector3 $pos, Block $block) {
        if($this->save === self::SAVE_TYPE_NORMAL) {
            $block = clone $block;
            $block->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
            $this->blocks[] = $block;
        }
        elseif($this->save === self::SAVE_TYPE_BLOCKMAP) {
            $this->blockMap[$pos->getX()][$pos->getY()][$pos->getZ()] = clone $block;
        }
    }

    /**
     * @return Block[] $blocks
     */
    public function getAll() {
        if($this->save === self::SAVE_TYPE_NORMAL) {
            return $this->blocks;
        }
        elseif($this->save === self::SAVE_TYPE_BLOCKMAP) {
            return parent::getAll();
        }
        return null;
    }

    /**
     * @param Block[] $blocks
     */
    public function setAll(array $blocks) {
        if($this->save === self::SAVE_TYPE_NORMAL) {
            $this->blocks = $blocks;
        }
        elseif ($this->save === self::SAVE_TYPE_BLOCKMAP) {
            parent::setAll($blocks);
        }
    }

    /**
     * TODO: Implement other modes
     *
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
     * TODO: Implement other modes
     *
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
     */
    public function setLevel(?Level $level) {
        $this->level = $level;
    }

    /**
     * @return Level $level
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return Vector3|null
     */
    public function getPlayerPosition(): ?Vector3 {
        return $this->playerPosition;
    }

    /**
     * @param Vector3 $position
     */
    public function setPlayerPosition(Vector3 $position) {
        $this->playerPosition = $position;
    }


    /**
     * @return array
     */
    public function toCopyData(): array {
        $data = [];
        $data["center"] = $this->playerPosition === null ? new Vector3(0, 0, 0) : $this->playerPosition->asVector3();
        $data["direction"] = 0;
        $data["rotated"] = false;

        foreach ($this->getAll() as $index => $block) {
            $data["data"][$index] = [$block->asVector3(), $block];
        }

        return $data;
    }

    /**
     * @param array $copyData
     * @param int $save
     *
     * @return BlockList
     */
    public static function fromCopyData(array $copyData, int $save = self::SAVE_TYPE_NORMAL): BlockList {
        $list = new BlockList($save);
        $list->setPlayerPosition($copyData["center"]);

        /**
         * @var Vector3 $vector3
         * @var Block $block
         */
        foreach ($copyData["data"] as [$vector3, $block]) {
            $list->addBlock($vector3, $block);
        }

        return $list;
    }

    /**
     * @param Level $level
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param int $save
     *
     * @return BlockList
     */
    public static function build(Level $level, Vector3 $pos1, Vector3 $pos2, int $save = self::SAVE_TYPE_NORMAL): BlockList {
        $blockList = new BlockList($save);
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

    /**
     * @return int
     */
    public function getSaveType(): int {
        return $this->save;
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
}