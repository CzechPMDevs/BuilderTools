<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;

/**
 * Class RotationHelper
 * @package czechpmdevs\buildertools\editors\data
 */
class RotationHelper {

    private const STAIRS_ROTATION_DATA = [0 => 2, 1 => 3, 2 => 1, 3 => 0, 4 => 6, 5 => 7, 6 => 5, 7 => 4];

    public static function rotate90(Block $block): Block {
        if(in_array($block->getId(), [
            BlockIds::COBBLESTONE_STAIRS,
            BlockIds::WOODEN_STAIRS,
            BlockIds::SPRUCE_STAIRS,
            BlockIds::BIRCH_STAIRS,
            BlockIds::JUNGLE_STAIRS,
            BlockIds::ACACIA_STAIRS,
            BlockIds::STONE_BRICK_STAIRS,
            BlockIds::SANDSTONE_STAIRS,
            BlockIds::RED_SANDSTONE_STAIRS,
            BlockIds::BRICK_STAIRS,
            BlockIds::NETHER_BRICK_STAIRS,
            BlockIds::QUARTZ_STAIRS,
            BlockIds::PURPUR_STAIRS
        ])) {
            $block->setDamage(self::STAIRS_ROTATION_DATA[$block->getDamage() % 8]);
        }

        return $block;
    }

    public static function rotate180(Block $block): Block {
        for($i = 0; $i < 2; $i++) {
            self::rotate90($block);
        }

        return $block;
    }

    public static function rotate270(Block $block): Block {
        for($i = 0; $i < 3; $i++) {
            self::rotate90($block);
        }

        return $block;
    }
}