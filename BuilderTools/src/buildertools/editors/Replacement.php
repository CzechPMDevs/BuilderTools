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
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

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
    public function replace($x1, $y1, $z1, $x2, $y2, $z2, Level $level, string $blocksToReplace, string $blocks, Player $player) {
        $count = 0;
        $undo = [];
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $vec = new Vector3($x, $y, $z);
                    if($this->inBlockArgs($level->getBlock($vec), $blocksToReplace)) {
                        $undo[] = $level->getBlock($vec);
                        $level->setBlock($vec, $this->getRandomBlock($blocks));
                        $count++;
                    }
                }
            }
        }
        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $undo);
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