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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\BuilderTools;
use pocketmine\block\Block;
use pocketmine\item\Item;

/**
 * Class Editor
 * @package buildertools\editors
 */
abstract class Editor {

    const CANCELLER = "Canceller";
    const COPIER = "Copier";
    const DECORATOR = "Decorator";
    const FILLER = "Filler";
    const FIXER = "Fixer";
    const NATURALIZER = "Naturalizer";
    const PRINTER = "Printer";
    const REPLACEMENT = "Replacement";

    /**
     * @return string
     */
    abstract function getName(): string;

    /**
     * @return BuilderTools
     */
    public function getPlugin(): BuilderTools {
        return BuilderTools::getInstance();
    }

    /**
     * @param string $string
     * @param int $id
     *
     * @return bool
     */
    public function isBlockInString(string $string, int $id): bool {
        $itemArgs = explode(",", $string);

        $items = [];
        foreach ($itemArgs as $itemString) {
            // Item::fromString() throws exception
            try {
                 $block = Item::fromString($itemString)->getBlock();
                 $items[] =  $block->getId();
            }
            catch (\Exception $exception) {}
        }

        return (bool)in_array($id, $items);
    }

    /**
     * @param string $string
     * @return Block $block
     */
    public function getBlockFromString(string $string): Block {
        $itemArgs = explode(",", $string);

        /** @var Item $item */
        $item = null;
        try {
            $item = Item::fromString($itemArgs[array_rand($itemArgs, 1)]);
        }
        catch (\Exception $exception) {
            $item = Item::get(Item::AIR);
        }

        if(!$item instanceof Item) return Block::get(Block::AIR);

        /** @var Block $block */
        $block = null;
        try {
            $block = $item->getBlock();
        }
        catch (\Exception $exception) {
            $block = Block::get(Block::AIR);
        }

        if(!$block instanceof Block) {
            return Block::get(Block::AIR);
        }

        return $block;
    }
}