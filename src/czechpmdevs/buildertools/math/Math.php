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

use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use function max;
use function min;
use const M_PI;

class Math {

    public const PI_360 = M_PI * 2;

    public static function ceilPosition(Position $position): Position {
        return Position::fromObject($position->ceil(), $position->getWorld());
    }

    /**
     * Returns distance^2 between (0, 0) and (x, y)
     *
     * @param int|float $x
     * @param int|float $y
     *
     * @return int|float
     */
    public static function lengthSquared2d($x, $y) {
        return ($x ** 2) + ($y ** 2);
    }

    /**
     * Returns distance^2 between (0, 0, 0) and (x, y, z)
     *
     * @param int|float $x
     * @param int|float $y
     * @param int|float $z
     *
     * @return int|float
     */
    public static function lengthSquared3d($x, $y, $z) {
        return ($x ** 2) + ($y ** 2) + ($z ** 2);
    }

    public static function calculateMinAndMaxValues(Vector3 $pos1, Vector3 $pos2, bool $clampY, ?int &$minX, ?int &$maxX, ?int &$minY, ?int &$maxY, ?int &$minZ, ?int &$maxZ): void {
        $minX = (int)min($pos1->getX(), $pos2->getX());
        $maxX = (int)max($pos1->getX(), $pos2->getX());
        $minZ = (int)min($pos1->getZ(), $pos2->getZ());
        $maxZ = (int)max($pos1->getZ(), $pos2->getZ());

        if($clampY) {
            $minY = (int)max(min($pos1->getY(), $pos2->getY(), World::Y_MAX), 0);
            $maxY = (int)min(max($pos1->getY(), $pos2->getY(), 0), World::Y_MAX);
        } else {
            $minY = (int)min($pos1->getY(), $pos2->getY());
            $maxY = (int)max($pos1->getY(), $pos2->getY());
        }
    }

    public static function selectionSize(Vector3 $pos1, Vector3 $pos2): int {
        Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

        return (($maxX - $minX) + 1) * (($maxY - $minY) + 1) * (($maxZ - $minZ) + 1);
    }
}