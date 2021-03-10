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

namespace czechpmdevs\buildertools\math;

use pocketmine\level\Position;
use pocketmine\math\Vector3;

class Math {

    public const PI_360 = M_PI * 2;

    /**
     * @deprecated
     * @link Math::ceilPosition()
     */
    public static function roundPosition(Position $position): Position {
        return self::ceilPosition($position);
    }

    public static function ceilPosition(Position $position): Position {
        return Position::fromObject($position->ceil(), $position->getLevel());
    }

    /**
     * Returns distance^2 between (0, 0) and (x, y)
     */
    public static function lengthSquared2d($x, $y) {
        return ($x ** 2) + ($y ** 2);
    }

    /**
     * Returns distance^2 between (0, 0, 0) and (x, y, z)
     */
    public static function lengthSquared3d($x, $y, $z) {
        return ($x ** 2) + ($y ** 2) + ($z ** 2);
    }

    public static function calculateAxisVec(Vector3 $pos1, Vector3 $pos2): Vector3 {
        $width = max($pos1->getX(), $pos2->getX()) - min($pos1->getX(), $pos2->getX());
        $height = max($pos1->getY(), $pos2->getY()) - min($pos1->getY(), $pos2->getY());
        $length = max($pos1->getZ(), $pos2->getZ()) - min($pos1->getZ(), $pos2->getZ());

        return (new Vector3($width, $height, $length))->add(1, 1, 1);
    }
}