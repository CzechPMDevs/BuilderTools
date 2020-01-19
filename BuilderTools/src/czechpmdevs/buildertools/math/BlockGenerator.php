<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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

/**
 * Class BlockGenerator
 * @package czechpmdevs\buildertools\math
 */
class BlockGenerator {

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     *
     * @return \Generator [$x, $y, $z]
     */
    public static function generateCuboid(Vector3 $pos1, Vector3 $pos2): \Generator {
        for($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); $x++) {
            for($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); $y++) {
                for($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); $z++) {
                    yield [$x, $y, $z];
                }
            }
        }
    }
}