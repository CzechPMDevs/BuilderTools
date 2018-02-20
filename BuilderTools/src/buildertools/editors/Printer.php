<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Printer
 * @package buildertools\editors
 */
class Printer extends Editor {

    const CUBE = 0;
    const SPHERE = 1;
    const CUSTOM = 2;

    /**
     * @param Position $position
     * @param int $brush
     * @param Block $block
     * @param int $mode
     * @param bool $fall
     * @param Player|null $player
     */
    public function draw(Position $position, int $brush, Block $block, int $mode, bool $fall = false, Player $player = null) {
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
                                    $position->getLevel()->setBlock(new Vector3($x, $bY, $z), $block, true, true);
                                }
                            }
                            else {
                                $vector = new Vector3($x, $y, $z);
                                $position->getLevel()->setBlock($vector, $block, false, false);
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
                            if(($xsqr + $ysqr + $zsqr) < ($brush*$brush)) {
                                if($fall) {
                                    $bY = $y;
                                    check2:
                                    if($bY-1 > 0 && $position->getLevel()->getBlock(new Vector3($x, $bY-1, $z))->getId() == 0) {
                                        $bY--;
                                        goto check2;
                                    }
                                    else {
                                        $position->getLevel()->setBlock(new Vector3($x, $bY, $z), $block, true, true);
                                    }
                                }
                                else {
                                    $vector = new Vector3($x, $y, $z);
                                    $position->getLevel()->setBlock($vector, $block, false, false);
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
                        $block->getLevel()->setBlock($vec->add($player->getX(), $y, $player->getZ()), $block, true, true);
                    }
                }
                break;
        }

    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}