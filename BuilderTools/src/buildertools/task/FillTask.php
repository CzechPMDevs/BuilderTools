<?php

declare(strict_types=1);

namespace buildertools\task;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\scheduler\Task;

/**
 * Class FillTask
 * @package buildertools\task
 */
class FillTask extends Task {

    /** @var array $toFill */
    public static $toFill = [];

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        $int = 0;
        foreach (self::$toFill as $progress) {
            $position = $progress[0];
            $block = $progress[1];
            if($position instanceof Position && $block instanceof Block) {
                $position->getLevel()->setBlock($position->asVector3(), $block, false, false);
                unset(self::$toFill[array_search($progress, self::$toFill)]);
                $int++;
                if($int == 10) {
                    return;
                }
            }
        }
    }

    public static function addBlock(Position $position, Block $block) {
        array_push(self::$toFill, [$position, $block]);
    }
}