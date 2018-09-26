<?php

/**
 * Copyright 2018 CzechPMDevs
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
use czechpmdevs\buildertools\editors\object\BlockList;
use czechpmdevs\buildertools\event\NaturalizeEvent;
use czechpmdevs\buildertools\utils\ConfigManager;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Naturalizer
 * @package buildertools\editors
 */
class Naturalizer extends Editor {

    /** @var BlockList $undo */
    protected $undo;

    public function __construct() {
        $this->undo = new BlockList();
    }

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     * @param Level $level
     * @param Player $player
     */
    public function naturalize(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Level $level, Player $player) {
        $settings = ConfigManager::getSettings($this);


        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                $this->fix(new Vector3($x, max($y1, $y2), $z), $level, min($y1, $y2));
            }
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $this->undo);
        $this->undo = [];
    }

    /**
     * @param Vector3 $vector3
     * @param Level $level
     * @param int $min
     */
    private function fix(Vector3 $vector3, Level $level, int $minY) {
        start:
        if($vector3->getX() > 1 && $level->getBlock($vector3)->getId() == Block::AIR) {
            $vector3 = $vector3->subtract(0, 1, 0);
            goto start;
        }

        if($vector3->getX() < 0) {
            return;
        }

        $this->undo->addBlock($vector3, $level->getBlock($vector3));
        $level->setBlockIdAt($vector3->getX(), $vector3->getY(), $vector3->getZ(), Block::GRASS);


        $r = rand(3, 4);

        for($y = 1; $y < $r; $y++) {
            $this->undo->addBlock($vector3, $level->getBlock($vector3->add(0, -$y, 0)));
            $level->setBlockIdAt($vector3->getX(), $vector3->getY()-$y, $vector3->getZ(), Block::DIRT);
        }

        for($y = $vector3->getY()-$r; $y >= $minY; $y--) {
            $this->undo->addBlock($vector3, $level->getBlock(new Vector3($vector3->getX(), $y, $vector3->getZ())));
            $level->setBlockIdAt($vector3->getX(), $y, $vector3->getZ(), Block::STONE);
        }
    }


    /**
     * @return string
     */
    public function getName(): string {
        return "Naturalizer";
    }
}