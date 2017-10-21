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
    #const HSPHERE = 2;

    // TODO: add custom $mode
    const CUSTOM = 3;

    /**
     * @param Position $position
     * @param int $brush
     * @param Block $block
     */
    public function draw(Position $position, int $brush, Block $block, int $mode) {
        switch ($mode) {
            case self::CUBE:
                for ($x = $position->getX()-$brush; $x <= $position->getX()+$brush; $x++) {
                    for ($y = $position->getY()-$brush; $y <= $position->getY()+$brush; $y++) {
                        for ($z = $position->getZ()-$brush; $z <= $position->getZ()+$brush; $z++) {
                            $vector = new Vector3($x, $y, $z);
                            $position->getLevel()->setBlock($vector, $block, false, false);
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
                                $position->getLevel()->setBlock(new Vector3($x, $y, $z), $block, false, false);
                            }
                        }
                    }
                }
                break;
            /*case self::HSPHERE:
                    $this->draw($position, $brush, $block, 1);
                    $this->draw(Position::fromObject($position->add(-1, -1, -1), $position->getLevel()), $brush, Block::get(Block::AIR), 1);
                break;*/
        }

    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Printer";
    }
}