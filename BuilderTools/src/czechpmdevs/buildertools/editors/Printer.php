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
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\utils\Math;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Printer
 * @package buildertools\editors
 */
class Printer extends Editor {

    public const CUBE = 0;
    public const SPHERE = 1;

    /**
     * @param Player $player
     * @param Position $center
     * @param Block $block
     * @param int $brush
     * @param int $mode
     * @param bool $fall
     */
    public function draw(Player $player, Position $center, Block $block, int $brush = 4, int $mode = 0, bool $fall = false) {
        $undoList = new BlockList;
        $center = Math::roundPosition($center);
        switch ($mode) {
            case self::CUBE:
                for ($x = $center->getX()-$brush; $x <= $center->getX()+$brush; $x++) {
                    for ($y = $center->getY()-$brush; $y <= $center->getY()+$brush; $y++) {
                        for ($z = $center->getZ()-$brush; $z <= $center->getZ()+$brush; $z++) {
                            if($fall) {
                                $finalPos = $this->throwBlock(new Position($x, $y, $z, $center->getLevel()), $block);
                                $undoList->addBlock($finalPos, $block);
                            } else {
                                if($y > 0) {
                                    $center->getLevel()->setBlockIdAt($x, $y, $z, $block->getId());
                                    $center->getLevel()->setBlockDataAt($x, $y, $z, $block->getDamage());
                                    $undoList->addBlock(new Vector3($x, $y, $z), $block);
                                }
                            }
                        }
                    }
                }
                break;

            case self::SPHERE:
                for ($x = $center->getX()-$brush; $x <= $center->getX()+$brush; $x++) {
                    $xsqr = ($center->getX()-$x) * ($center->getX()-$x);
                    for ($y = $center->getY()-$brush; $y <= $center->getY()+$brush; $y++) {
                        $ysqr = ($center->getY()-$y) * ($center->getY()-$y);
                        for ($z = $center->getZ()-$brush; $z <= $center->getZ()+$brush; $z++) {
                            $zsqr = ($center->getZ()-$z) * ($center->getZ()-$z);
                            if(($xsqr + $ysqr + $zsqr) <= ($brush*$brush)) {
                                if($fall) {
                                    $finalPos = $this->throwBlock(new Position($x, $y, $z, $center->getLevel()), $block);
                                    $undoList->addBlock($finalPos, $block);
                                }
                                else {
                                    if($y > 0) {
                                        $center->getLevel()->setBlockIdAt($x, $y, $z, $block->getId());
                                        $center->getLevel()->setBlockDataAt($x, $y, $z, $block->getDamage());
                                        $undoList->addBlock(new Vector3($x, $y, $z), $block);
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }
    }

    /**
     * @param Position $position
     * @param Block $block
     *
     * @return Vector3 $pos
     */
    private function throwBlock(Position $position, Block $block): Vector3 {
        $level = $position->getLevel();

        $x = $position->getX();
        $y = $position->getY();
        $z = $position->getZ();

        $finalY = $y;

        for($a = $y+1; $a > 0 && $level->getBlockIdAt($x, $a-1, $z) == Block::AIR; $a--) {
            $finalY = $a-1;
        }

        $level->setBlockIdAt($x, $finalY, $z, $block->getId());
        $level->setBlockDataAt($x, $finalY, $z, $block->getDamage());
        return new Vector3($x, $finalY, $z);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param $blocks
     *
     * @return EditorResult
     */
    public function makeSphere(Player $player, Position $center, int $radius, $blocks): EditorResult {
        $center = Math::roundPosition($center);
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());
        for($x = $center->getX()-$radius; $x < $center->getX()+$radius; $x++) {
            $xsqr = ($center->getX()-$x) * ($center->getX()-$x);
            for($y = $center->getY()-$radius; $y < $center->getY()+$radius; $y++) {
                $ysqr = ($center->getY()-$y) * ($center->getY()-$y);
                for($z = $center->getZ()-$radius; $z < $center->getZ()+$radius; $z++) {
                    $zsqr = ($center->getZ()-$z) * ($center->getZ()-$z);
                    if(($xsqr + $ysqr + $zsqr) <= ($radius*$radius)) {
                        $blockList->addBlock(new Vector3($x, $y, $z), $this->getBlockFromString($blocks));
                    }
                }
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $blockList, ["saveUndo" => true]);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param $blocks
     *
     * @return EditorResult
     */
    public function makeCube(Player $player, Position $center, int $radius, $blocks): EditorResult {
        $center = Math::roundPosition($center);
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());
        for($x = $center->getX()-$radius; $x < $center->getX()+$radius; $x++) {
            for($y = $center->getY()-$radius; $y < $center->getY()+$radius; $y++) {
                for($z = $center->getZ()-$radius; $z < $center->getZ()+$radius; $z++) {
                    $blockList->addBlock(new Vector3($x, $y, $z), $this->getBlockFromString($blocks));
                }
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $blockList, ["saveUndo" => true]);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}