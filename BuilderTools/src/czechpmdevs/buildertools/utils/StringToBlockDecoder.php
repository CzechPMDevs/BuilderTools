<?php /** @noinspection PhpUnused */

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

use ArrayOutOfBoundsException;
use pocketmine\item\ItemFactory;

final class StringToBlockDecoder {

    /** @var string */
    private string $string;

    /** @var int[] */
    private array $blockIdMap = [];
    /** @var int[] */
    private array $blockMap = [];

    public function __construct(string $string) {
        $this->string = $string;
        $this->decode();
    }

    /**
     * @return bool Returns if the string contains
     * any valid blocks
     */
    public function isValid(): bool {
        return count($this->blockMap) != 0;
    }

    /**
     * Reads next block from the string,
     * @throws ArrayOutOfBoundsException if string is not valid.
     */
    public function nextBlock(?int &$id, ?int &$meta): void {
        $hash = $this->blockMap[array_rand($this->blockMap)];

        $id = $hash >> 4;
        $meta = $hash & 0x0f;
    }

    /**
     * @return bool Returns if the block is in the array
     */
    public function containsBlock(int $id, int $meta): bool {
        return in_array($id << 4 | $meta, $this->blockMap);
    }

    /**
     * @return bool Returns if block id is in the array
     */
    public function containsBlockId(int $id): bool {
        return in_array($id, $this->blockIdMap);
    }

    public function decode(): void {
        $split = explode(",", $this->string);
        foreach ($split as $entry) {
            $count = 1;
            $block = $entry;
            if(strpos($entry, "%") !== false) {
                $p = substr($entry, 0, $pos = strpos($entry, "%"));
                if(!is_numeric($p)) {
                    continue;
                }

                $count = min(100, (int)$p);
                $block = substr($entry, $pos + 1);
            }

            $item = ItemFactory::fromStringSingle($block);
            $class = $item->getBlock();
            if($class->getId() == 0 && $item->getId() != 0) {
                continue;
            }

            for($i = 0; $i < $count; $i++) {
                $this->blockIdMap[] = $class->getId();
                $this->blockMap[] = $class->getId() << 4 | $class->getDamage();
            }
        }
    }
}