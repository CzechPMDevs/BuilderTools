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
}