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

namespace czechpmdevs\buildertools\blockstorage;

use Generator;
use function explode;

/**
 * Actually this is the fastest way I found for
 * storing block in array as XZ => [y1, y2, ...]
 *
 * This class is used for pre-building objects. So we needn't chunk iterator
 * to switch sub chunk many times
 *
 * If you find any better and faster method, contact me through discord
 * - VixikHD#2992
 *
 * This made change from 15.9 sec to 13.8 sec => -2.1 sec
 * Update: (removed min & max values) 12.9 => -0.9 sec
 * ( //cyl air 20 100 )
 *
 * TODO - Add this to PHP
 */
class FastBlockMap {

    /** @var int[][] $entries */
    public array $entries = [];

    public function addBlock(int $x, int $y, int $z): void {
        $this->entries["$x:$z"][] = $y;
    }

    /**
     * @return Generator<int, int, int>
     */
    public function readBlocks(): Generator {
        foreach ($this->entries as $xz => $yy) {
            $split = explode(":", $xz);

            $x = (int)$split[0];
            $z = (int)$split[1];

            foreach ($yy as $y) {
                yield [$x, $y, $z];
            }
        }
    }
}