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

use pocketmine\block\BlockIds;

class Fixer extends Editor {

    private const BLOCK_FIX_DATA = [
        158 => [BlockIds::WOODEN_SLAB, 0],
        125 => [BlockIds::DOUBLE_WOODEN_SLAB, ""],
        188 => [BlockIds::FENCE, 0],
        189 => [BlockIds::FENCE, 1],
        190 => [BlockIds::FENCE, 2],
        191 => [BlockIds::FENCE, 3],
        192 => [BlockIds::FENCE, 4],
        193 => [BlockIds::FENCE, 5],
        166 => [BlockIds::INVISIBLE_BEDROCK, 0],
        208 => [BlockIds::GRASS_PATH, 0],
        198 => [BlockIds::END_ROD, 0],
        126 => [BlockIds::WOODEN_SLAB, ""],
        95  => [BlockIds::STAINED_GLASS, ""],
        199 => [BlockIds::CHORUS_PLANT, 0],
        202 => [BlockIds::PURPUR_BLOCK, 0],
        251 => [BlockIds::CONCRETE, 0],
        204 => [BlockIds::PURPUR_BLOCK, 0]
    ];

    public function fixBlock(int &$id, int &$damage) {
        if(isset(self::BLOCK_FIX_DATA[$id])) {
            if(is_int(self::BLOCK_FIX_DATA[$id][1])) {
                $damage = self::BLOCK_FIX_DATA[$id][1];
            }
            $id = self::BLOCK_FIX_DATA[$id][0];
        }

        if($id == BlockIds::TRAPDOOR || $id == BlockIds::IRON_TRAPDOOR) {
            $damage = $this->fixTrapdoorMeta($damage);
        }

        if($id == BlockIds::WOODEN_BUTTON || $id == BlockIds::STONE_BUTTON) {
            $damage = $this->fixButtonMeta($damage);
        }
    }

    private function fixButtonMeta(int $meta): int {
        return (6 - $meta) % 6;
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

    public function getName(): string {
        return "Fixer";
    }
}
