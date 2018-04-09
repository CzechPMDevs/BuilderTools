<?php

declare(strict_types=1);

namespace buildertools\editors;

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
     */
    public function addDecoration(Position $center, string $blocks, int $radius, int $percentage) {
        $undo = [];
        for ($x = $center->getX()-$radius; $x <= $center->getX()+$radius; $x++) {
            for ($z = $center->getZ()-$radius; $z <= $center->getZ()+$radius; $z++) {
                if(rand($percentage, 100) == 100) {
                    $y = $center->getY()+$radius;
                    check:
                    if($y > 0) {
                        $vec = new Vector3($x, $y, $z);
                        if($center->getLevel()->getBlock($vec)->getId() == 0) {
                            $y--;
                            goto check;
                        }
                        else {
                            $blockArgs = explode($blocks,",");
                            array_push($undo, $center->getLevel()->getBlock($vec));
                            $center->getLevel()->setBlock($vec, Item::fromString($blockArgs[array_rand($blockArgs,1)])->getBlock(), true, true);
                        }
                    }
                }
            }
        }
    }
}
