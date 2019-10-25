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

use pocketmine\level\Position;
use pocketmine\math\Vector3;

/**
 * Class Math
 * @package buildertools\utils
 */
class Math {

    /**
     * @param Vector3 $vector3
     * @return Vector3 $vector3
     */
    public static function roundVector3(Vector3 $vector3): Vector3 {
        return new Vector3((int)round($vector3->getX()), (int)round($vector3->getY()), (int)round($vector3->getZ()));
    }

    /**
     * @param Position $position
     * @return Position $position
     */
    public static function roundPosition(Position $position): Position {
        return Position::fromObject(self::roundVector3($position), $position->getLevel());
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return float|int
     */
    public static function lengthSq($x, $y, $z = null) {
        if($z === null) return ($x * $x) + ($y * $y);
        return ($x * $x) + ($y * $y) + ($z * $z);
    }

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @return Vector3
     */
    public static function calculateAxisVec(Vector3 $pos1, Vector3 $pos2) {
        $width = max($pos1->getX(), $pos2->getX())-min($pos1->getX(), $pos2->getX());
        $height = max($pos1->getY(), $pos2->getY())-min($pos1->getY(), $pos2->getY());
        $length = max($pos1->getZ(), $pos2->getZ())-min($pos1->getZ(), $pos2->getZ());
        return (new Vector3($width, $height, $length))->add(1, 1, 1);
    }
}