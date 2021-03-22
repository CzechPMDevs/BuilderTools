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

namespace czechpmdevs\buildertools\editors;

use pocketmine\block\BlockLegacyIds;
use pocketmine\utils\SingletonTrait;
use function is_int;

class Fixer {
    use SingletonTrait;

    private const BLOCK_FIX_DATA = [
        158 => [BlockLegacyIds::WOODEN_SLAB, 0],
        125 => [BlockLegacyIds::DOUBLE_WOODEN_SLAB, ""],
        188 => [BlockLegacyIds::FENCE, 0],
        189 => [BlockLegacyIds::FENCE, 1],
        190 => [BlockLegacyIds::FENCE, 2],
        191 => [BlockLegacyIds::FENCE, 3],
        192 => [BlockLegacyIds::FENCE, 4],
        193 => [BlockLegacyIds::FENCE, 5],
        166 => [BlockLegacyIds::INVISIBLE_BEDROCK, 0],
        208 => [BlockLegacyIds::GRASS_PATH, 0],
        198 => [BlockLegacyIds::END_ROD, 0],
        126 => [BlockLegacyIds::WOODEN_SLAB, ""],
        95  => [BlockLegacyIds::STAINED_GLASS, ""],
        199 => [BlockLegacyIds::CHORUS_PLANT, 0],
        202 => [BlockLegacyIds::PURPUR_BLOCK, 0],
        251 => [BlockLegacyIds::CONCRETE, 0],
        204 => [BlockLegacyIds::PURPUR_BLOCK, 0]
    ];

    public function fixBlock(int &$id, int &$damage): void {
        if(isset(self::BLOCK_FIX_DATA[$id])) {
            if(is_int(self::BLOCK_FIX_DATA[$id][1])) {
                $damage = self::BLOCK_FIX_DATA[$id][1];
            }
            $id = self::BLOCK_FIX_DATA[$id][0];
        }

        if($id == BlockLegacyIds::TRAPDOOR || $id == BlockLegacyIds::IRON_TRAPDOOR) {
            $damage = $this->fixTrapdoorMeta($damage);
        }

        if($id == BlockLegacyIds::WOODEN_BUTTON || $id == BlockLegacyIds::STONE_BUTTON) {
            $damage = $this->fixButtonMeta($damage);
        }
    }

    private function fixButtonMeta(int $meta): int {
        return ((6 - $meta) % 6) & 0xf;
    }

    private function fixTrapdoorMeta(int $meta): int {
        $key = $meta >> 2;
        if($key == 0) {
            return 3 - $meta;
        } elseif($key == 3) {
            return 27 - $meta;
        } else {
            return 15 - $meta;
        }
    }
}
