<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\utils;

use pocketmine\block\BlockIds;

/**
 * Class RotationHelper
 * @package czechpmdevs\buildertools\editors\data
 */
class RotationHelper {

    private const STAIRS_IDS = [BlockIds::COBBLESTONE_STAIRS, BlockIds::WOODEN_STAIRS, BlockIds::SPRUCE_STAIRS, BlockIds::BIRCH_STAIRS, BlockIds::JUNGLE_STAIRS, BlockIds::ACACIA_STAIRS, BlockIds::STONE_BRICK_STAIRS, BlockIds::SANDSTONE_STAIRS, BlockIds::RED_SANDSTONE_STAIRS, BlockIds::BRICK_STAIRS, BlockIds::NETHER_BRICK_STAIRS, BlockIds::QUARTZ_STAIRS, BlockIds::PURPUR_STAIRS];
    private const STAIRS_ROTATION_DATA = [0 => 2, 1 => 3, 2 => 1, 3 => 0, 4 => 6, 5 => 7, 6 => 5, 7 => 4];

    public static function rotate(int $degrees, int &$id, int &$meta) {
        if($degrees == 90) {
            self::rotate90($id, $meta);
        } elseif($degrees == 180) {
            self::rotate180($id, $meta);
        } elseif($degrees == 270) {
            self::rotate270($id, $meta);
        }
    }

    public static function rotate90(int &$id, int &$meta) {
        if(in_array($id, self::STAIRS_IDS)) {
            $meta = self::STAIRS_ROTATION_DATA[$meta % 8];
        }
    }

    public static function rotate180(int &$id, int &$meta) {
        if(in_array($id, self::STAIRS_IDS)) {
            $meta = self::STAIRS_ROTATION_DATA[$meta % 8];
            $meta = self::STAIRS_ROTATION_DATA[$meta % 8];
        }
    }

    public static function rotate270(int &$id, int &$meta) {
        if(in_array($id, self::STAIRS_IDS)) {
            $meta = self::STAIRS_ROTATION_DATA[$meta % 8];
            $meta = self::STAIRS_ROTATION_DATA[$meta % 8];
            $meta = self::STAIRS_ROTATION_DATA[$meta % 8];
        }
    }
}