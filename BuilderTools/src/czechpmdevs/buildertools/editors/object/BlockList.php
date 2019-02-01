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
    //public const SAVE_TYPE_OPTIMIZED = 0x03; TODO

    /** @var bool $save */
    private $save;

    /** @var Block[] $blocks */
    private $blocks = [];

    /** @var Level $level */
    private $level;

    /** @var Vector3 $playerPosition */
    private $playerPosition = null;

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
        $data["center"] = $this->playerPosition === null ? new Vector3(0, 0, 0) : $this->playerPosition;
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
}