<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\task\FillTask;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

/**
 * Class Filler
 * @package buildertools\editors
 */
class Filler extends Editor {

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     * @param Level $level
     * @param string $blocks
     * @param bool $force
     * @return int
     */
    public function fill(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Level $level, string $blocks, bool $force):int {
        $count = 0;
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $count++;
                    $args = explode(",", strval($blocks));
                    #$level->setBlock(new Vector3($x, $y, $z), Item::fromString($args[array_rand($args, 1)])->getBlock());
                    if($force === false) {
                        FillTask::addBlock(new Position($x, $y, $z, $level), Item::fromString($args[array_rand($args, 1)])->getBlock());
                    }
                    else {
                        $level->setBlock(new Vector3($x, $y, $z), Item::fromString($args[array_rand($args, 1)])->getBlock());
                    }

                }
            }
        }
        return $count;
    }

    public function getName(): string {
        return "Filler";
    }
}