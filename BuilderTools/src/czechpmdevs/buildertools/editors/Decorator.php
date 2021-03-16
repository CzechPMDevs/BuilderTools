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
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\SingletonTrait;

class Decorator {
    use SingletonTrait;

    /**
     * TODO
     */
    public function addDecoration(Position $center, string $blocks, int $radius, int $percentage, ?Player $player = null): void {
        $undo = new BlockArray();
        $stringToBlockDecoder = new StringToBlockDecoder($blocks);

        for ($x = $center->getX() - $radius; $x <= $center->getX() + $radius; $x++) {
            /** @var int $x */
            for ($z = $center->getZ() - $radius; $z <= $center->getZ() + $radius; $z++) {
                /** @var int $z */
                if (rand(1, 100) <= $percentage) {
                    /** @var int $y */
                    $y = $center->getY() + $radius;
                    check:
                    if ($y > 0) {
                        $vec = new Vector3($x, $y, $z);
                        if ($center->getLevelNonNull()->getBlock($vec)->getId() == 0) {
                            $y--;
                            goto check;
                        } else {
                            $undo->addBlock($vec, $center->getLevelNonNull()->getBlockIdAt($x, $y, $z), $center->getLevelNonNull()->getBlockDataAt($x, $y, $z));

                            $stringToBlockDecoder->nextBlock($id, $meta);
                            $center->getLevelNonNull()->setBlock($vec->add(0, 1), $id, $meta);
                        }
                    }
                }
            }
        }

        if($player !== null) {
            Canceller::getInstance()->addStep($player, $undo);
        }
    }
}
