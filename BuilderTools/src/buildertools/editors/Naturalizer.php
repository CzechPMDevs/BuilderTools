<?php

declare(strict_types=1);

namespace buildertools\editors;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class Naturalizer
 * @package buildertools\editors
 */
class Naturalizer extends Editor {

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     */
    public function naturalize(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Level $level) {
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                $this->fix(new Vector3($x, max($y1, $y2), $z), $level, min($y1, $y2));
            }
        }
    }

    private function fix(Vector3 $vector3, Level $level, int $minX) {
        start:
        if($level->getBlock($vector3)->getId() == Block::AIR) {
            $vector3 = $vector3->add(0, -1, 0);
            goto start;
        }

        $level->setBlock($vector3, Block::get(Block::GRASS));

        $r = rand(3, 4);

        for($y = 1; $y < $r; $y++) {
            $level->setBlock($vector3->add(0, -$y, 0), Block::get(Block::DIRT));
        }

        for($y = $vector3->getY()-$r; $y > $minX; $y--) {
            $level->setBlock(new Vector3($vector3->getX(), $y, $vector3->getZ()), Block::get(Block::STONE));
        }
    }


    /**
     * @return string
     */
    public function getName(): string {
        return "Naturalizer";
    }
}