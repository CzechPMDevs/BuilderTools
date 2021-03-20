<?php /** @noinspection PhpDocSignatureInspection */

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

use Generator;
use pocketmine\math\Vector3;
use function max;
use function min;

class BlockGenerator {

    /**
     * @return Generator<int[]>
     */
    public static function fillCuboid(Vector3 $pos1, Vector3 $pos2, bool $hollow = false): Generator {
        $minX = (int)min($pos1->getX(), $pos2->getX());
        $maxX = (int)max($pos1->getX(), $pos2->getX());
        $minZ = (int)min($pos1->getZ(), $pos2->getZ());
        $maxZ = (int)max($pos1->getZ(), $pos2->getZ());
        $minY = (int)min($pos1->getY(), $pos2->getY());
        $maxY = (int)max($pos1->getY(), $pos2->getY());

        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                for($y = $minY; $y <= $maxY; ++$y) {
                    if($hollow && ($x != $minX && $x != $maxX) && ($y != $minY && $y != $maxY) && ($z != $minZ && $z != $maxZ)) {
                        continue;
                    }

                    yield [$x, $y, $z];
                }
            }
        }
    }

    /**
     * @return Generator<int[]>
     */
    public static function generateCube(int $radius, bool $hollow = false): Generator {
        for($x = -$radius; $x <= $radius; ++$x) {
            for ($z = -$radius; $z <= $radius; ++$z) {
                for ($y = -$radius; $y <= $radius; ++$y) {
                    if($hollow && ($x != $radius && $y != $radius && $z != $radius)) {
                        continue;
                    }

                    yield [$x, $y, $z];
                }
            }
        }
    }

    /**
     * @return Generator<int[]>
     */
    public static function generateSphere(int $radius, bool $hollow = false): Generator {
        $incDivX = 0;
        for($x = 0; $x <= $radius; ++$x) {
            $divX = $incDivX; // divX = dividedX = x / radius
            $incDivX = ($x + 1) / $radius; // incDivX = increasedDividedX = (x + 1) / radius

            $incDivY = 0;
            for($y = 0; $y <= $radius; ++$y) {
                $divY = $incDivY;
                $incDivY = ($y + 1) / $radius;

                $incDivZ = 0;
                for($z = 0; $z <= $radius; ++$z) {
                    $divZ = $incDivZ;
                    $incDivZ = ($z + 1) / $radius;

                    $lengthSquared = Math::lengthSquared3d($divX, $divY, $divZ);
                    if($lengthSquared > 1) { // x**2 + y**2 + z**2 < r**2
                        if ($z == 0) {
                            if ($y == 0) {
                                break 2;
                            }
                            break;
                        }
                        continue;
                    }

                    if($hollow && Math::lengthSquared3d($incDivX, $divY, $divZ) <= 1 && Math::lengthSquared3d($divX, $incDivY, $divZ) <= 1 && Math::lengthSquared3d($divX, $divY, $incDivZ) <= 1) {
                        continue;
                    }

                    foreach(self::generateMissingBlocks3d($x, $y, $z) as $vector3) {
                        yield $vector3;
                    }
                }
            }
        }
    }

    /**
     * @return Generator<int[]>
     */
    public static function generateCylinder(int $radius, int $height, bool $hollow = false): Generator {
        $incDivX = 0;
        for($x = 0; $x <= $radius; ++$x) {
            $divX = $incDivX;
            $incDivX = ($x + 1) / $radius;
            $incDivZ = 0;
            for($z = 0; $z <= $radius; ++$z) {
                $divZ = $incDivZ;
                $incDivZ = ($z + 1) / $radius;

                $lengthSquared = Math::lengthSquared2d($divX, $divZ);
                if($lengthSquared > 1) { // checking if can skip blocks outside of circle
                    if($z == 0) {
                        break 2;
                    }

                    break;
                }

                if($hollow && Math::lengthSquared2d($divX, $incDivZ) <= 1 && Math::lengthSquared2d($incDivX, $divZ) <= 1) {
                    continue;
                }

                for($y = 0; $y < $height; ++$y) {
                    foreach (self::generateMissingBlocks2d($x, $y, $z) as $vector3) {
                        yield $vector3;
                    }
                }
            }
        }
    }

    /**
     * @return Generator<int[]>
     */
    public static function generatePyramid(int $size, bool $hollow = false): Generator {
        $currentLevelHeight = $size;
        for($y = 0; $y <= $size; ++$y) {
            for($x = 0; $x <= $currentLevelHeight; ++$x) {
                for($z = 0; $z <= $currentLevelHeight; ++$z) {
                    if($hollow && ($x != $currentLevelHeight && $z != $currentLevelHeight)) {
                        continue;
                    }

                    foreach (self::generateMissingBlocks2d($x, $y, $z) as $vector3) {
                        yield $vector3;
                    }
                }
            }
            $currentLevelHeight--;
        }
    }

    /**
     * Changes only X and Z, Y is not affected
     *
     * @return Generator<int[]>
     */
    public static function generateMissingBlocks2d(int $x, int $y, int $z): Generator {
        yield [$x, $y, $z];
        yield [-$x, $y, $z];
        yield [$x, $y, -$z];
        yield [-$x, $y, -$z];
    }

    /**
     * @return Generator<int[]>
     */
    public static function generateMissingBlocks3d(int $x, int $y, int $z): Generator {
        yield [$x, $y, $z];
        yield [-$x, $y, $z];
        yield [$x, -$y, $z];
        yield [$x, $y, -$z];
        yield [-$x, -$y, $z];
        yield [$x, -$y, -$z];
        yield [-$x, $y, -$z];
        yield [-$x, -$y, -$z];
    }
}