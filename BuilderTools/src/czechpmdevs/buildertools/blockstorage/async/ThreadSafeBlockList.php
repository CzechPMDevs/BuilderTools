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

namespace czechpmdevs\buildertools\blockstorage\async;

use czechpmdevs\buildertools\blockstorage\BlockList;
use Generator;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use Volatile;

/**
 * Class BlockVolatile
 * @package czechpmdevs\buildertools\blockstorage\async
 */
class ThreadSafeBlockList extends Volatile {

    /**
     * @param Vector3 $vector3
     * @param ThreadSafeBlock $block
     */
    public function addBlock(Vector3 $vector3, ThreadSafeBlock $block) {
        $this[] = $block->setComponents($vector3);
    }

    /**
     * @return Generator<ThreadSafeBlock>
     */
    public function getAll(): Generator {
        foreach ($this as $block) {
            yield $block;
        }
    }

    /**
     * Returns block list, should be called only on main thread
     *
     * @return BlockList
     */
    public function toBlockList(): BlockList {
        $blockList = new BlockList();
        while ($block = $this->pop()) {
            /** @var ThreadSafeBlock $block */
            $blockList->addBlock($block->asVector3(), Block::get($block->getId(), $block->getDamage()));
        }

        return $blockList;
    }
}