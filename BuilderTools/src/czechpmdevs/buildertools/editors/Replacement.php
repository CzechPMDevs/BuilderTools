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

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\SingletonTrait;

class Replacement {
    use SingletonTrait;

    public function prepareReplace(Vector3 $pos1, Vector3 $pos2, Level $level, string $blocks, string $replace): BlockArray {
        $stringToBlockDecoder = new StringToBlockDecoder($blocks);
        $anotherStringToBlockDecoder = new StringToBlockDecoder($replace);

        $updateLevelData = new BlockArray();
        $updateLevelData->setLevel($level);

        foreach (BlockGenerator::fillCuboid($pos1, $pos2) as $vector3) {
            $block = $level->getBlock($vector3);
            if($stringToBlockDecoder->containsBlock($block->getId(), $block->getDamage())) {
                $anotherStringToBlockDecoder->nextBlock($id, $meta);
                $updateLevelData->addBlock($vector3, $id, $meta);
            }
        }

        return $updateLevelData;
    }
}