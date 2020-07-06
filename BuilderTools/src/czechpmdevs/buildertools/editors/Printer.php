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
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\math\Math;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Printer
 * @package buildertools\editors
 */
class Printer extends Editor {

    public const CUBE = 0x00;
    public const SPHERE = 0x01;
    public const CYLINDER = 0x02;
    public const HCUBE = 0x03;
    public const HSPHERE = 0x04;
    public const HCYLINDER = 0x05;

    public const X_AXIS = 0x01;
    public const Y_AXIS = 0x02;
    public const Z_AXIS = 0x03;

    /**
     * @param Player $player
     * @param Position $center
     * @param Block $block
     * @param int $brush
     * @param int $mode
     * @param bool $fall
     */
    public function draw(Player $player, Position $center, Block $block, int $brush = 4, int $mode = 0x00, bool $fall = false) {
        $undoList = new BlockList();
        $center = Math::roundPosition($center);
        switch ($mode) {
            case self::CUBE:
                foreach (BlockGenerator::generateCuboid($center->subtract($brush, $brush, $brush), $center->add($brush, $brush, $brush)) as [$x, $y, $z]) {
                    if ($fall) {
                        $finalPos = $this->throwBlock(new Position($x, $y, $z, $center->getLevel()), $block);
                        $undoList->addBlock($finalPos, $block);

                    } else {
                        if ($y > 0) {
                            $level = $center->getLevel();
                            $level->setBlockIdAt($x, $y, $z, $block->getId());
                            $level->setBlockDataAt($x, $y, $z, $block->getDamage());

                            $undoList->addBlock(new Vector3($x, $y, $z), $block);
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
                                        $level = $center->getLevel();
                                        $level->setBlockIdAt($x, $y, $z, $block->getId());
                                        $level->setBlockDataAt($x, $y, $z, $block->getDamage());

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

        $level->setBlockIdAt($x, $finalY, $z, $block->getId());
        $level->setBlockDataAt($x, $finalY, $z, $block->getDamage());

        return new Vector3($x, $finalY, $z);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param $blocks
     * @param bool $hollow
     *
     * @return EditorResult
     */
    public function makeSphere(Player $player, Position $center, int $radius, $blocks, bool $hollow = false): EditorResult {
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

                    if($hollow) {
                        if(Math::lengthSq($nextXn, $yn, $zn) <= 1 and Math::lengthSq($xn, $nextYn, $zn) <= 1 and Math::lengthSq($xn, $yn, $nextZn) <= 1){
                            continue;
                        }
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
        return $this->makeSphere($player, $center, $radius, $blocks, true);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param int $height
     * @param $blocks
     * @param bool $hollow
     *
     * @return EditorResult
     */
    public function makeCylinder(Player $player, Position $center, int $radius, int $height, $blocks, bool $hollow = false): EditorResult {
        $center = Math::roundPosition($center);
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());

        if ($height == 0) {
            return new EditorResult(0, 0, true);
        } elseif ($height < 0) {
            $height = -$height;
            $center = $center->setComponents($center->getY(), $height, $center->getZ());
        }

        if ($center->getFloorY() < 0) {
            return new EditorResult(0,0, true);
        }

        $invRadiusX = 1 / $radius;
        $invRadiusZ = 1 / $radius;


        $nextXn = 0;
        $breakX = false;
        for ($x = 0; $x <= $radius && $breakX === false; ++$x) {
            $xn = $nextXn;
            $nextXn = ($x + 1) * $invRadiusX;
            $nextZn = 0;
            $breakZ = false;
            for ($z = 0; $z <= $radius && $breakZ === false; ++$z) {
                $zn = $nextZn;
                $nextZn = ($z + 1) * $invRadiusZ;

                $distanceSq = Math::lengthSq($xn, $zn);
                if ($distanceSq > 1) {
                    if ($z == 0) {
                        $breakX = true;
                    }
                    $breakZ = true;
                }

                if ($hollow) {
                    if (Math::lengthSq($nextXn, $zn) <= 1 && Math::lengthSq($xn, $nextZn) <= 1) {
                        continue;
                    }
                }

                for ($y = 0; $y < $height; ++$y) {
                    $blockList->addBlock($center->add($x, $y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, $y, $z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add($x, $y, -$z), $this->getBlockFromString($blocks));
                    $blockList->addBlock($center->add(-$x, $y, -$z), $this->getBlockFromString($blocks));
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
     * @param int $height
     * @param $blocks
     *
     * @return EditorResult
     */
    public function makeHollowCylinder(Player $player, Position $center, int $radius, int $height, $blocks): EditorResult {
        return $this->makeCylinder($player, $center, $radius, $height, $blocks, true);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $size
     * @param $blocks
     * @param bool $hollow
     *
     * @return EditorResult
     */
    public function makePyramid(Player $player, Position $center, int $size, $blocks, bool $hollow = false): EditorResult {
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());
        $height = $size;
        for ($y = 0; $y <= $height; ++$y) {
            $size--;
            for ($x = 0; $x <= $size; ++$x) {
                for ($z = 0; $z <= $size; ++$z) {
                    if ((!$hollow && $z <= $size && $x <= $size) || $z == $size || $x == $size) {
                        $blockList->addBlock($center->add($x, $y, $z), $this->getBlockFromString($blocks));
                        $blockList->addBlock($center->add(-$x, $y, $z), $this->getBlockFromString($blocks));
                        $blockList->addBlock($center->add($x, $y, -$z), $this->getBlockFromString($blocks));
                        $blockList->addBlock($center->add(-$x, $y, -$z), $this->getBlockFromString($blocks));
                    }
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
     * @param int $size
     * @param $blocks
     *
     * @return EditorResult
     */
    public function makeHollowPyramid(Player $player, Position $center, int $size, $blocks): EditorResult {
        return $this->makePyramid($player, $center, $size, $blocks, true);
    }

    /**
     * @param Player $player
     * @param Position $center
     * @param int $radius
     * @param $blocks
     * @param bool $hollow
     *
     * @return EditorResult
     */
    public function makeCube(Player $player, Position $center, int $radius, $blocks, bool $hollow = false): EditorResult {
        $center = Math::roundPosition($center);
        $blockList = new BlockList();
        $blockList->setLevel($center->getLevel());
        for($x = -$radius; $x <= $radius; $x++) {
            for($y = -$radius; $y <= $radius; $y++) {
                for($z = -$radius; $z <= $radius; $z++) {
                    if($hollow) {
                        if(in_array($radius, [$x, $y, $z, -$x, -$y, -$z])) $blockList->addBlock($center->add($x, $y, $z), $this->getBlockFromString($blocks));
                    } else {
                        $blockList->addBlock($center->add($x, $y, $z), $this->getBlockFromString($blocks));
                    }
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
        return $this->makeCube($player, $center, $radius, $blocks, true);
    }



    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}