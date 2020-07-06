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
use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\editors\object\EditorResult;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector2;
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
     *
     * @return EditorResult
     */
    public function naturalize(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Level $level, Player $player) {
        $list = new BlockList();
        $list->setLevel($level);

        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                $this->fix($list, new Vector2($x, $z), (int)min($y1, $y2), (int)max($y1, $y2), $level);
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $list);
    }

    /**
     * @param BlockList $list
     * @param Vector2 $vector2
     * @param Level $level
     * @param int $minY
     * @param int $maxY
     */
    private function fix(BlockList $list, Vector2 $vector2, int $minY, int $maxY, Level $level) {
        $x = (int)$vector2->getX();
        $z = (int)$vector2->getY();

        $blockY = null;
        for($y = $minY; $y < $maxY; $y++) {
            if($level->getBlockAt($x, $y, $z)->getId() !== Block::AIR && ($blockY === null || $blockY < $y)) {
                $blockY = $y;
            }
        }

        if($blockY === null) return;

        for($y = $blockY; $y > $minY; $y--) {
            switch ($blockY-$y) {
                case 0:
                    $list->addBlock(new Vector3($x, $y, $z), Block::get(Block::GRASS));
                    break;
                case 1:
                case 2:
                case 3:
                    if($level->getBlockAt($x, $y, $z)->getId() != Block::AIR) {
                        $list->addBlock(new Vector3($x, $y, $z), Block::get(Block::DIRT));
                    }
                    break;
                case 4:
                    if($level->getBlockAt($x, $y, $z)->getId() != Block::AIR) {
                        $list->addBlock(new Vector3($x, $y, $z), (rand(0, 1) ? Block::get(Block::DIRT) : Block::get(Block::STONE)));
                    }
                    break;
                default:
                    if($level->getBlockAt($x, $y, $z)->getId() != Block::AIR) {
                        $list->addBlock(new Vector3($x, $y, $z), Block::get(Block::STONE));
                    }
            }
        }
    }


    /**
     * @return string
     */
    public function getName(): string {
        return "Naturalizer";
    }
}