<?php

declare(strict_types=1);

namespace buildertools\editors;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

/**
 * Class Printer
 * @package buildertools\editors
 */
class Printer extends Editor {

    const CUBE = 0;
    const SPHERE = 1;

    // TODO: add custom $mode
    const CUSTOM = 2;

    /**
     * @param Position $position
     * @param int $brush
     * @param Block $block
     */
    public function draw(Position $position, int $brush, Block $block, int $mode, bool $fall) {
        switch ($mode) {
            case self::CUBE:
                for ($x = $position->getX()-$brush; $x <= $position->getX()+$brush; $x++) {
                    for ($y = $position->getY()-$brush; $y <= $position->getY()+$brush; $y++) {
                        for ($z = $position->getZ()-$brush; $z <= $position->getZ()+$brush; $z++) {
                            if($fall) {
                                $bY = $y;
                                check1:
                                if($position->getLevel()->getBlock(new Vector3($x, $bY-1, $z))->getId() == 0) {
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
                                    if($position->getLevel()->getBlock(new Vector3($x, $bY-1, $z))->getId() == 0) {
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
        }

    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}