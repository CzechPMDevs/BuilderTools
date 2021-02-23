<?php

/**
 * Copyright (C) 2018-2021  CzechPMDevs
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

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\math\Math;
use pocketmine\math\Vector3;

/**
 * Class RotationUtil
 * @package czechpmdevs\buildertools\math
 */
class RotationUtil {

    public const VALID_DEGREES = [self::ROTATE_0, self::ROTATE_90, self::ROTATE_180, self::ROTATE_270];

    public const ROTATE_0 = 0;
    public const ROTATE_90 = 90;
    public const ROTATE_180 = 180;
    public const ROTATE_270 = 270;
    public const ROTATE_360 = 0;

    public const X_AXIS = 0;
    public const Y_AXIS = 1;
    public const Z_AXIS = 2;

    /**
     * @param BlockArray $blockArray
     * @param int $axis
     * @param int $degrees
     *
     * @return BlockArray
     */
    public static function rotate(BlockArray $blockArray, int $axis, int $degrees): BlockArray {
        $degrees = Math::getBasicDegrees(360 - $degrees);
        if($degrees == 0) {
            return $blockArray;
        }

        $modifiedBlockArray = new BlockArray();
        switch ($axis) {
            case self::Y_AXIS:
                $x = $y = $z = $id = $meta = 0;
                while ($blockArray->hasNext()) {
                    $blockArray->readNext($x, $y, $z, $id, $meta);

                    $dist = sqrt(Math::lengthSquared2d($x, $z));
                    $alfa = Math::getBasicDegrees(atan2($y, $x) + $degrees);
                    $modifiedBlockArray->addBlock(new Vector3($dist * cos($alfa), $y, $dist * sin($alfa)), $id, $meta);
                }
                $blockArray->buffer = $modifiedBlockArray->buffer;
                return $blockArray;
            case self::X_AXIS:
                $x = $y = $z = $id = $meta = 0;
                while ($blockArray->hasNext()) {
                    $blockArray->readNext($x, $y, $z, $id, $meta);

                    $dist = sqrt(Math::lengthSquared2d($y, $z));
                    $alfa = Math::getBasicDegrees(atan2($y, $z) + $degrees);
                    $modifiedBlockArray->addBlock(new Vector3($x, $dist * cos($alfa), $dist * sin($alfa)), $id, $meta);
                }
                $blockArray->buffer = $modifiedBlockArray->buffer;
                return $blockArray;
            case self::Z_AXIS:
                $x = $y = $z = $id = $meta = 0;
                while ($blockArray->hasNext()) {
                    $blockArray->readNext($x, $y, $z, $id, $meta);

                    $dist = sqrt(Math::lengthSquared2d($x, $y));
                    $alfa = Math::getBasicDegrees(atan2($x, $y) + $degrees);
                    $modifiedBlockArray->addBlock(new Vector3($dist * cos($alfa), $dist * sin($alfa), $z), $id, $meta);
                }
                $blockArray->buffer = $modifiedBlockArray->buffer;
                return $blockArray;
            default:
                return $blockArray;
        }
    }

    /**
     * @param int $degrees
     *
     * @return bool
     */
    public static function areDegreesValid(int $degrees): bool {
        $degrees = Math::getBasicDegrees($degrees);

        return in_array($degrees, self::VALID_DEGREES);
    }

    /**
     * @param int $degrees
     *
     * @return int
     */
    public static function getRotation(int $degrees): int {
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