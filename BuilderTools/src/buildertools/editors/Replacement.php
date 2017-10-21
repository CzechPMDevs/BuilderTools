<?php

declare(strict_types=1);

namespace buildertools\editors;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class Replacement
 * @package buildertools\editors
 */
class Replacement extends Editor {

    /**
     * @param $x1
     * @param $y1
     * @param $z1
     * @param $x2
     * @param $y2
     * @param $z2
     * @param Level $level
     * @param string $blocksToReplace
     * @param string $blocks
     * @return int
     */
    public function replace($x1, $y1, $z1, $x2, $y2, $z2, Level $level, string $blocksToReplace, string $blocks) {
        $count = 0;
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $vec = new Vector3($x, $y, $z);
                    if($this->inBlockArgs($level->getBlock($vec), $blocksToReplace)) {
                        $level->setBlock($vec, $this->getRandomBlock($blocks));
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * @param Block $block
     * @param string $blocks
     * @return bool
     */
    public function inBlockArgs(Block $block, string $blocks) {
        $return = false;
        $args = explode(",", $blocks);
        foreach ($args as $arg) {
            $item = Item::fromString($arg);
            if($block->getId() == $item->getId()) {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * @param string $blockArgs
     * @return Block $block
     */
    public function getRandomBlock(string $blockArgs):Block {
        $args = explode(",", $blockArgs);
        return Item::fromString($args[array_rand($args, 1)])->getBlock();
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Replacement";
    }
}