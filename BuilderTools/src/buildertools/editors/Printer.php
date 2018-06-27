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

namespace buildertools\editors;

use buildertools\BuilderTools;
use buildertools\editors\object\BlockList;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\math\VectorMath;
use pocketmine\Player;

/**
 * Class Printer
 * @package buildertools\editors
 */
class Printer extends Editor {

    const CUSTOM = -1;
    const CUBE = 0;
    const SPHERE = 1;
    const HSPHERE = 2;

    /**
     * @param Position $position
     * @param int $brush
     * @param Block $block
     * @param int $mode
     * @param bool $fall
     * @param Player|null $player
     * @param bool $fastFill
     */
    public function draw(Position $position, int $brush, Block $block, int $mode, bool $fall = false, Player $player = null, bool $fastFill = true) {
        $blockList = new BlockList;
        $blockList->setLevel($position->getLevel());

        switch ($mode) {
            case self::CUBE:
                for ($x = $position->getX()-$brush; $x <= $position->getX()+$brush; $x++) {
                    for ($y = $position->getY()-$brush; $y <= $position->getY()+$brush; $y++) {
                        for ($z = $position->getZ()-$brush; $z <= $position->getZ()+$brush; $z++) {
                            if($fall) {
                                $bY = $y;
                                check1:
                                if($bY-1 > 0 && $position->getLevel()->getBlock(new Vector3($x, $bY-1, $z))->getId() == 0) {
                                    $bY--;
                                    goto check1;
                                }
                                else {
                                    $blockList->addBlock(new Vector3($x, $bY, $z), $block);
                                }
                            }
                            else {
                                if(!($y < 0)) {
                                    $blockList->addBlock(new Vector3($x, $y, $z), $block);
                                }

                            }
                        }
                    }
                }
                break;

            case self::SPHERE:
                for ($x = $position->getX()-$brush; $x <= $position->getX()+$brush; $x++) {
                    $xsqr = ($position->getX()-$x) * ($position->getX()-$x);
                    for ($y = $position->getY()-$brush; $y <= $position->getY()+$brush; $y++) {
                        $ysqr = ($position->getY()-$y) * ($position->getY()-$y);
                        for ($z = $position->getZ()-$brush; $z <= $position->getZ()+$brush; $z++) {
                            $zsqr = ($position->getZ()-$z) * ($position->getZ()-$z);
                            if(($xsqr + $ysqr + $zsqr) <= ($brush*$brush)) {
                                if($fall) {
                                    $bY = $y;
                                    check2:
                                    if($bY-1 > 0 && $position->getLevel()->getBlock(new Vector3($x, $bY-1, $z))->getId() == 0) {
                                        $bY--;
                                        goto check2;
                                    }
                                    else {
                                        $blockList->addBlock(new Vector3($x, $bY, $z), $block);
                                    }
                                }
                                else {
                                    if(!($y < 0)) {
                                        $blockList->addBlock(new Vector3($x, $y, $z), $block);
                                    }
                                }
                            }
                        }
                    }
                }
                break;

            case self::HSPHERE: // DOES NOT WORK
                $toChange = [];
                for ($x = $position->getX()-$brush; $x <= $position->getX()+$brush; $x++) {
                    $xsqr = ($position->getX()-$x) * ($position->getX()-$x);
                    for ($y = $position->getY()-$brush; $y <= $position->getY()+$brush; $y++) {
                        $ysqr = ($position->getY()-$y) * ($position->getY()-$y);
                        for ($z = $position->getZ()-$brush; $z <= $position->getZ()+$brush; $z++) {
                            $zsqr = ($position->getZ()-$z) * ($position->getZ()-$z);
                            if(($asqr = ($xsqr + $ysqr + $zsqr)) <= $brush << 2 && $asqr < ($brush-1) << 2) {
                                if($fall) {
                                    $bY = $y;
                                    if($bY-1 > 0 && $position->getLevel()->getBlock(new Vector3($x, $bY-1, $z))->getId() == 0) {
                                        $bY--;
                                    }
                                    else {
                                        $undo[] = $position->getLevel()->getBlock(new Vector3($x, $bY, $z));
                                        $position->getLevel()->setBlock(new Vector3($x, $bY, $z), $block, true, true);
                                    }
                                }
                                else {
                                    if(!($y < 0)) {
                                        $undo[] = $position->getLevel()->getBlock(new Vector3($x, $y, $z));
                                        $vector = new Vector3($x, $y, $z);
                                        $position->getLevel()->setBlock($vector, $block, true, true);
                                    }
                                }
                            }
                        }
                    }
                }
                break;

            case self::CUSTOM:
                /** @var Copier $copier */
                $copier = BuilderTools::getEditor("Copier");
                if(empty($copier->copyData[$player->getName()])) {
                    $player->sendMessage(BuilderTools::getPrefix()."§cUse //copy first!");
                    return;
                }

                /** @var array $blocks */
                $blocks = $copier->copyData[$player->getName()]["data"];

                if(!$fall) {


                    /**
                     * @var Vector3 $vec
                     * @var Block $block
                     */
                    foreach ($blocks as [$vec, $block]) {
                        $player->getLevel()->setBlock($vec->add($player->asVector3()), $block, true, true);
                    }
                }

                else {
                    /**
                     * @var Vector3 $vec
                     * @var Block $block
                     */
                    foreach ($blocks as [$vec, $block]) {
                        $y = $vec->getY();
                        check3:
                        if($block->getLevel()->getBlock(new Vector3($vec->getX()+$player->getX(), $y, $vec->getZ()+$player->getZ()))->getId() == Block::AIR && $y >= 0) {
                            $y--;
                            goto check3;
                        }
                        $undo[] = $position->getLevel()->getBlock($vec->add($player->getX(), $y, $player->getZ()));
                        $block->getLevel()->setBlock($vec->add($player->getX(), $y, $player->getZ()), $block, true, true);
                    }
                }
                break;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(static::FILLER);
        $filler->fill($player, $blockList, $fastFill);
    }

    /**
     * @param Position $vector
     */
    public function makeSphere(Position $vector) {

    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}