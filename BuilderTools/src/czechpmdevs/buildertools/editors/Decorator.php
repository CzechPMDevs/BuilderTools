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

use czechpmdevs\buildertools\BuilderTools;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

/**
 * Class Decorator
 * @package worldfixer\editors
 */
class Decorator extends Editor {

    /**
     * @return string
     */
    public function getName(): string {
        return "Decorator";
    }

    /**
     * @param Position $center
     * @param string $blocks
     * @param int $radius
     * @param int $percentage
     * @param null $player
     */
    public function addDecoration(Position $center, string $blocks, int $radius, int $percentage, $player = null) {
        $undo = [];
        for ($x = $center->getX()-$radius; $x <= $center->getX()+$radius; $x++) {
            for ($z = $center->getZ()-$radius; $z <= $center->getZ()+$radius; $z++) {
                if(rand(1, 100) <= $percentage) {
                    $y = $center->getY()+$radius;
                    check:
                    if($y > 0) {
                        $vec = new Vector3($x, $y, $z);
                        if($center->getLevel()->getBlock($vec)->getId() == 0) {
                            $y--;
                            goto check;
                        }
                        else {
                            $blockArgs = explode(",", $blocks);
                            array_push($undo, $center->getLevel()->getBlock($vec));
                            $undo[] = $center->getLevel()->getBlock($vec->add(0, 1));
                            $center->getLevel()->setBlock($vec->add(0, 1), Item::fromString($blockArgs[array_rand($blockArgs,1)])->getBlock());
                        }
                    }
                }
            }
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $undo);
    }
}
