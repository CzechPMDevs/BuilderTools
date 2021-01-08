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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\blockstorage\BlockList;
use czechpmdevs\buildertools\blockstorage\UpdateLevelData;
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
     * Blocks whose will replaced
     * @param string $blocks
     *
     * Blocks whose will placed
     * @param string $replace
     *
     * @return UpdateLevelData
     */
    public function prepareReplace(Vector3 $pos1, Vector3 $pos2, Level $level, string $blocks, string $replace): UpdateLevelData {
        $updateLevelData = new UpdateLevelData();
        $updateLevelData->setLevel($level);

        foreach (BlockGenerator::fillCuboid($pos1, $pos2) as [$x, $y, $z]) {
            if($this->isBlockInString($blocks, $level->getBlockAt($x, $y, $z))) {
                $updateLevelData->addBlock(new Vector3($x, $y, $z), ...$this->getBlockArgsFromString($replace));
            }
        }

        return $updateLevelData;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Replacement";
    }
}