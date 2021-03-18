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
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use function mt_rand;

class Decorator {
    use SingletonTrait;

    /**
     * TODO
     */
    public function addDecoration(Position $center, string $blocks, int $radius, int $percentage, ?Player $player = null): void {
        $undo = new BlockArray();
        $stringToBlockDecoder = new StringToBlockDecoder($blocks);

        for ($x = $center->getX() - $radius; $x <= $center->getX() + $radius; ++$x) {
            /** @var int $x */
            for ($z = $center->getZ() - $radius; $z <= $center->getZ() + $radius; ++$z) {
                /** @var int $z */
                if (mt_rand(1, 100) <= $percentage) {
                    /** @var int $y */
                    $y = $center->getY() + $radius;
                    check:
                    if ($y > 0) {
                        $vec = new Vector3($x, $y, $z);
                        if ($center->getWorld()->getBlock($vec)->getId() == 0) {
                            $y--;
                            goto check;
                        } else {
                            $block = $player->getWorld()->getBlockAt($x, $y, $z);
                            $undo->addBlock($vec, $block->getId(), $block->getMeta());

                            $stringToBlockDecoder->nextBlock($id, $meta);
                            $center->getWorld()->setBlock($vec->add(0, 1, 0), $id, $meta);
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
