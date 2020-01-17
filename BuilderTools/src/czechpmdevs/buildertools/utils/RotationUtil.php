<?php

/**
 * Copyright (C) 2018-2019  CzechPMDevs
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

namespace czechpmdevs\buildertools\utils;

use czechpmdevs\buildertools\editors\object\BlockList;
use pocketmine\block\UnknownBlock;
use pocketmine\math\Vector3;

/**
 * Class RotationUtil
 * @package czechpmdevs\buildertools\utils
 */
class RotationUtil {

    public const VALID_DEGREES = [self::ROTATE_0, self::ROTATE_90, self::ROTATE_180, self::ROTATE_270];

    public const ROTATE_0 = 0;
    public const ROTATE_90 = 90;
    public const ROTATE_180 = 180;
    public const ROTATE_270 = 270;
    public const ROTATE_360 = 0; // yes, it's same as 0

    public const X_AXIS = 0;
    public const Y_AXIS = 1;
    public const Z_AXIS = 2;

    /**
     * @param BlockList $list
     * @param int $axis
     * @param int $rotation
     *
     * @return BlockList
     */
    public static function rotate(BlockList $list, int $axis, int $rotation = RotationUtil::ROTATE_0): BlockList {
        if($rotation === self::ROTATE_0) {
            return $list;
        }

        $blockList = new BlockList($list->getSaveType());
        $blockList->setLevel($list->getLevel());

        $backwardsVector = self::moveBlocksToCoordinatesAxisOrigin($blockList);
        self::addUnknownToSquare($blockList, $axis); // it makes square

        switch ($rotation) {
            case self::ROTATE_90:
                $blockList = self::rotate90($list, $axis);
                break;
            case self::ROTATE_180:
                $blockList = self::rotate180($list, $axis);
                break;
            case self::ROTATE_270:
                $blockList = self::rotate270($list, $axis);
                break;
        }

        self::removeUnknownBlocks($blockList);
        $blockList->getMetadata()->recalculateMetadata();
        $blockList->add($backwardsVector);

        return $blockList;
    }

    /**
     * @param BlockList $list
     * @param int $axis
     *
     * @return BlockList
     */
    private static function rotate90(BlockList $list, int $axis): BlockList {
        switch ($axis) {
            case self::Y_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $x = $metadata->maxZ-$block->getZ();
                    $z = $block->getX();
                    $newList->addBlock(new Vector3($x, $block->getY(), $z), $block);
                }
                return $newList;

            case self::X_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $y = $metadata->maxY-$block->getZ();
                    $z = $block->getY();
                    $newList->addBlock(new Vector3($block->getX(), $y, $z), $block);
                }
                return $newList;

            case self::Z_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $x = $metadata->maxX-$block->getY();
                    $y = $block->getX();
                    $newList->addBlock(new Vector3($x, $y, $block->getZ()), $block);
                }
                return $newList;

            default:
                return $list;
        }
    }

    /**
     * @param BlockList $list
     * @param int $axis
     *
     * @return BlockList
     */
    private static function rotate180(BlockList $list, int $axis): BlockList {
        switch ($axis) {
            case self::Y_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $x = $metadata->maxX-$block->getX();
                    $z = $metadata->maxZ-$block->getZ();
                    $newList->addBlock(new Vector3($x, $block->getY(), $z), $block);
                }
                return $newList;

            case self::X_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $y = $metadata->maxY-$block->getY();
                    $z = $metadata->maxZ-$block->getZ();
                    $newList->addBlock(new Vector3($block->getX(), $y, $z), $block);
                }
                return $newList;

            case self::Z_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $x = $metadata->maxX-$block->getX();
                    $y = $metadata->maxY-$block->getY();
                    $newList->addBlock(new Vector3($x, $y, $block->getZ()), $block);
                }
                return $newList;

            default:
                return $list;
        }
    }

    /**
     * Stairs -> meta =+ 1
     *
     * @param BlockList $list
     * @param int $axis
     *
     * @return BlockList
     */
    private static function rotate270(BlockList $list, int $axis): BlockList {
        switch ($axis) {
            case self::Y_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $x = $block->getZ();
                    $z = $metadata->maxX - $block->getX();
                    $newList->addBlock(new Vector3($x, $block->getY(), $z), $block);
                }
                return $newList;

            case self::X_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $y = $block->getZ();
                    $z = $metadata->maxZ - $block->getY();
                    $newList->addBlock(new Vector3($block->getX(), $y, $z), $block);
                }
                return $newList;

            case self::Z_AXIS:
                $newList = new BlockList($list->getSaveType());
                $newList->setLevel($list->getLevel());
                $newList->setPlayerPosition($list->getPlayerPosition());

                $metadata = $list->getMetadata();

                foreach ($list->getAll() as $block) {
                    $x = $block->getY();
                    $y = $metadata->maxY - $block->getX();
                    $newList->addBlock(new Vector3($x, $y, $block->getZ()), $block);
                }
                return $newList;

            default:
                return $list;
        }
    }

    /**
     * @param BlockList $list
     * @param int $axis
     */
    private static function addUnknownToSquare(BlockList $list, int $axis) {
        switch ($axis) {
            case self::Y_AXIS:
                $blocks = [];
                foreach ($list->getAll() as $block) {
                    $blocks[$block->getY()][] = "$block->x:$block->z";
                }

                $max = max($list->getMetadata()->maxX, $list->getMetadata()->maxZ);
                if($max % 2 == 0)
                    $max++;

                $list->getMetadata()->maxX = $list->getMetadata()->maxZ = $max;

                for($i = 0; $i <= $max; $i++) { // x
                    for($j = 0; $j <= $max; $j++) { // z
                        for($y = 0; $y < $list->getMetadata()->maxY; $y++) {
                            if(!in_array("$i:$j", $blocks[$y])) {
                                $list->addBlock(new Vector3($i, $y, $j), new UnknownBlock(-1)); // if pmmp starts checking it, i will have to find empty id
                            }
                        }
                    }
                }
                break;

            case self::X_AXIS:
                $blocks = [];
                foreach ($list->getAll() as $block) {
                    $blocks[$block->getX()][] = "$block->y:$block->z";
                }

                $max = max($list->getMetadata()->maxY, $list->getMetadata()->maxZ);
                if($max % 2 == 0)
                    $max++;

                $list->getMetadata()->maxY = $list->getMetadata()->maxZ = $max;

                for($i = 0; $i <= $max; $i++) { // y
                    for($j = 0; $j <= $max; $j++) { // z
                        for($x = 0; $x < $list->getMetadata()->maxX; $x++) {
                            if(!in_array("$i:$j", $blocks[$x])) {
                                $list->addBlock(new Vector3($x, $i, $j), new UnknownBlock(-1)); // if pmmp starts checking it, i will have to find empty id
                            }
                        }
                    }
                }
                break;

            case self::Z_AXIS:
                $blocks = [];
                foreach ($list->getAll() as $block) {
                    $blocks[$block->getZ()][] = "$block->x:$block->y";
                }

                $max = max($list->getMetadata()->maxX, $list->getMetadata()->maxY);
                if($max % 2 == 0)
                    $max++;

                $list->getMetadata()->maxX = $list->getMetadata()->maxY = $max;

                for($i = 0; $i <= $max; $i++) { // x
                    for($j = 0; $j <= $max; $j++) { // y
                        for($z = 0; $z < $list->getMetadata()->maxZ; $z++) {
                            if(!in_array("$i:$j", $blocks[$z])) {
                                $list->addBlock(new Vector3($i, $j, $z), new UnknownBlock(-1)); // if pmmp starts checking it, i will have to find empty id
                            }
                        }
                    }
                }
                break;
        }
    }

    /**
     * @param BlockList $list
     * @return BlockList
     */
    private static function removeUnknownBlocks(BlockList $list): BlockList {
        $blocks = [];
        foreach ($list->getAll() as $block) {
            if(!$block instanceof UnknownBlock) {
                $blocks[] = $block;
            }
        }

        $list->setAll($blocks);
        return $list;
    }

    /**
     * @param BlockList $list
     * @return Vector3
     */
    private static function moveBlocksToCoordinatesAxisOrigin(BlockList $list): Vector3 {
        $metadata = $list->getMetadata();

        $toAdd = new Vector3(-$metadata->minX, -$metadata->minY, -$metadata->minZ);

        /*
        foreach ($list->getAll() as $block) {
            $block->setComponents($block->getX() + $toAdd->getX(), $block->getY() + $toAdd->getY(), $block->getZ() + $toAdd->getZ());
        }*/
        $list->add($toAdd);

        $metadata->recalculateMetadata();

        return (new Vector3())->subtract($toAdd);
    }

    /**
     * @param int $degrees
     *
     * @return bool
     */
    public static function areDegreesValid(int $degrees) {
        $degrees = Math::getBasicDegrees($degrees);

        return in_array($degrees, self::VALID_DEGREES);
    }

    /**
     * @param int $degrees
     *
     * @return int
     */
    public static function getRotation(int $degrees) {
        $basic = Math::getBasicDegrees($degrees);

        switch ($basic) {
            case 0:
                return self::ROTATE_0;
            case 90:
                return self::ROTATE_90;
            case 180:
                return self::ROTATE_180;
            case 270:
                return self::ROTATE_270;
        }

        return self::ROTATE_360;
    }
}