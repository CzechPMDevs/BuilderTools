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

use czechpmdevs\buildertools\blockstorage\SelectionData;
use czechpmdevs\buildertools\math\Math;
use pocketmine\math\Vector3;

class RotationUtil {

    public const VALID_DEGREES = [RotationUtil::ROTATE_0, RotationUtil::ROTATE_90, RotationUtil::ROTATE_180, RotationUtil::ROTATE_270];

    public const ROTATE_0 = 0;
    public const ROTATE_90 = 90;
    public const ROTATE_180 = 180;
    public const ROTATE_270 = 270;
    public const ROTATE_360 = 0;

    public static function rotate(SelectionData $blockArray, int $axis, int $degrees): SelectionData {
        if($degrees == 0) {
            return $blockArray;
        }

        $rad = deg2rad($degrees % 360);

        /** @var Vector3 $diff */
        $diff = $blockArray->getPlayerPosition();

        $modifiedBlockArray = new SelectionData();
        switch ($axis) {
            case Axis::Y_AXIS:
                while ($blockArray->hasNext()) {
                    $blockArray->readNext($x, $y, $z, $id, $meta);
                    RotationHelper::rotate($degrees, $id, $meta);

                    $dist = sqrt(Math::lengthSquared2d($x - $diff->getX(), $z - $diff->getZ()));
                    $alfa = fmod(atan2($z - $diff->getZ(), $x - $diff->getX()) + $rad, Math::PI_360);
                    $modifiedBlockArray->addBlock(new Vector3((int)round($dist * cos($alfa)) + $diff->getX(), $y, (int)round($dist * sin($alfa)) + $diff->getZ()), $id, $meta);
                }

                $blockArray->coords = $modifiedBlockArray->coords;
                $blockArray->offset = 0;
                return $blockArray;
            case Axis::X_AXIS:
                while ($blockArray->hasNext()) {
                    $blockArray->readNext($x, $y, $z, $id, $meta);

                    $dist = sqrt(Math::lengthSquared2d($y - $diff->getY(), $z - $diff->getZ()));
                    $alfa = fmod(atan2($y - $diff->getY(), $z - $diff->getZ()) + $rad, Math::PI_360);
                    $y = (int)round($dist * cos($alfa)) + $diff->getX();
                    if($y < 0) {
                        continue;
                    }
                    $blockArray->addBlock(new Vector3($x, $y, (int)round($dist * sin($alfa)) + $diff->getZ()), $id, $meta);
                }

                $blockArray->coords = $modifiedBlockArray->coords;
                $blockArray->offset = 0;
                return $blockArray;
            case Axis::Z_AXIS:
                while ($blockArray->hasNext()) {
                    $blockArray->readNext($x, $y, $z, $id, $meta);

                    $dist = sqrt(Math::lengthSquared2d($x - $diff->getX(), $y - $diff->getY()));
                    $alfa = fmod(atan2($x - $diff->getX(), $y - $diff->getY()) + $rad, Math::PI_360);
                    $y = (int)round($dist * sin($alfa));
                    if($y < 0) {
                        continue;
                    }
                    $modifiedBlockArray->addBlock(new Vector3((int)round($dist * cos($alfa)), $y, $z), $id, $meta);
                }

                $blockArray->coords = $modifiedBlockArray->coords;
                $blockArray->offset = 0;
                return $blockArray;
            default:
                return $blockArray;
        }
    }

    public static function areDegreesValid(int $degrees): bool {
        $degrees = fmod($degrees, 360);

        return in_array($degrees, RotationUtil::VALID_DEGREES);
    }

    public static function getRotation(int $degrees): int {
        $basic = fmod($degrees, 360);

        switch ($basic) {
            case 0:
                return RotationUtil::ROTATE_0;
            case 90:
                return RotationUtil::ROTATE_90;
            case 180:
                return RotationUtil::ROTATE_180;
            case 270:
                return RotationUtil::ROTATE_270;
        }

        return RotationUtil::ROTATE_360;
    }
}