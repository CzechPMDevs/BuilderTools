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
    public const HSPHERE = 2;

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
                                    $center->getLevel()->setBlockAt($x, $y, $z, $block);
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
                                        $center->getLevel()->setBlockAt($x, $y, $z, $block);
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

        for($a = $y+1; $a > 0 && $level->getBlockAt($x, $a-1, $z)->getId() == Block::AIR; $a--) {
            $finalY = $a-1;
        }

        $level->setBlockAt($x, $finalY, $z, $block);
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

        $invRadiusX = 1 / $radius;
        $invRadiusY = 1 / $radius;
        $invRadiusZ = 1 / $radius;

        $nextXn = 0;
        $breakX = false;
        for($x = 0; $x <= $radius and $breakX === false; ++$x){
            $xn = $nextXn;
            $nextXn = ($x + 1) * $invRadiusX;
            $nextYn = 0;
            $breakY = false;
            for($y = 0; $y <= $radius and $breakY === false; ++$y){
                $yn = $nextYn;
                $nextYn = ($y + 1) * $invRadiusY;
                $nextZn = 0;
                for($z = 0; $z <= $radius; ++$z){
                    $zn = $nextZn;
                    $nextZn = ($z + 1) * $invRadiusZ;
                    $distanceSq = Math::lengthSq($xn, $yn, $zn);
                    if($distanceSq > 1){
                        if($z === 0){
                            if($y === 0){
                                $breakX = true;
                                $breakY = true;
                                break;
                            }
                            $breakY = true;
                            break;
                        }
                        break;
                    }

                    $blockList->addBlock($center->add($x, $y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, $y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, -$y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, $y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, -$y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, -$y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, $y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, -$y, -$z), $this->getBlockFromString($blocks));
                }
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $blockList);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param $blocks
     *
     * @return EditorResult
     */
    public function makeHollowSphere(Player $player, Position $center, int $radius, $blocks): EditorResult {
        $center = Math::roundPosition($center);
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());

        $invRadiusX = 1 / $radius;
        $invRadiusY = 1 / $radius;
        $invRadiusZ = 1 / $radius;

        $nextXn = 0;
        $breakX = false;
        for($x = 0; $x <= $radius and $breakX === false; ++$x){
            $xn = $nextXn;
            $nextXn = ($x + 1) * $invRadiusX;
            $nextYn = 0;
            $breakY = false;
            for($y = 0; $y <= $radius and $breakY === false; ++$y){
                $yn = $nextYn;
                $nextYn = ($y + 1) * $invRadiusY;
                $nextZn = 0;
                for($z = 0; $z <= $radius; ++$z){
                    $zn = $nextZn;
                    $nextZn = ($z + 1) * $invRadiusZ;
                    $distanceSq = Math::lengthSq($xn, $yn, $zn);
                    if($distanceSq > 1){
                        if($z === 0){
                            if($y === 0){
                                $breakX = true;
                                $breakY = true;
                                break;
                            }
                            $breakY = true;
                            break;
                        }
                        break;
                    }

                    if(Math::lengthSq($nextXn, $yn, $zn) <= 1 and Math::lengthSq($xn, $nextYn, $zn) <= 1 and Math::lengthSq($xn, $yn, $nextZn) <= 1){
                        continue;
                    }

                    $blockList->addBlock($center->add($x, $y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, $y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, -$y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, $y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, -$y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, -$y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, $y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, -$y, -$z), $this->getBlockFromString($blocks));
                }
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $blockList);
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
        return $filler->fill($player, $blockList);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param $blocks
     *
     * @return EditorResult
     */
    public function makeHollowCube(Player $player, Position $center, int $radius, $blocks): EditorResult {
        $center = Math::roundPosition($center);
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());
        for($x = $center->getX()-$radius; $x < $center->getX()+$radius; $x++) {
            for($y = $center->getY()-$radius; $y < $center->getY()+$radius; $y++) {
                for($z = $center->getZ()-$radius; $z < $center->getZ()+$radius; $z++) {
                    if($x == $center->getX()+$radius || $y == $center->getY()+$radius || $z == $center->getZ()+$radius) $blockList->addBlock(new Vector3($x, $y, $z), $this->getBlockFromString($blocks));
                }
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $blockList);
    }



    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}