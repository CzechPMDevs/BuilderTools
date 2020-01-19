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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\math\BlockGenerator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class Replacement
 * @package buildertools\editors
 */
class Replacement extends Editor {

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param Level $level
     *
     * Blocks which will replaced
     * @param string $blocks
     *
     * Blocks which will placed
     * @param string $replace
     *
     * @return BlockList
     */
    public function prepareReplace(Vector3 $pos1, Vector3 $pos2, Level $level, string $blocks, string $replace): BlockList {
        $blockList = new BlockList;
        $blockList->setLevel($level);

        foreach (BlockGenerator::generateCuboid($pos1, $pos2) as [$x, $y, $z]) {
            if($this->isBlockInString($blocks, $level->getBlockAt($x, $y, $z)->getId())) {
                $blockList->addBlock(new Vector3($x, $y, $z), $this->getBlockFromString($replace));
            }
        }

        return $blockList;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Replacement";
    }
}