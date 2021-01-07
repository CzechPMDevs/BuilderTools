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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\BuilderTools;
use Exception;
use pocketmine\block\Block;
use pocketmine\item\Item;

/**
 * Class Editor
 * @package buildertools\editors
 */
abstract class Editor {

    public const CANCELLER = "Canceller";
    public const COPIER = "Copier";
    public const DECORATOR = "Decorator";
    public const FILLER = "Filler";
    public const FIXER = "Fixer";
    public const NATURALIZER = "Naturalizer";
    public const PRINTER = "Printer";
    public const REPLACEMENT = "Replacement";

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
     * @param Block $block
     *
     * @return bool
     */
    public function isBlockInString(string $string, Block $block): bool {
        $itemArgs = explode(",", $string);
        $checkMeta = strpos($string, ":") !== false || Item::fromString($string)->getDamage() !== 0;

        $items = [];
        foreach ($itemArgs as $itemString) {
            // Item::fromString() throws exception
            try {
                 $blockInString = Item::fromString($itemString)->getBlock();
                 if($checkMeta) {
                     $items[] =  (string)$blockInString->getId() . ":" . (string)$blockInString->getDamage();
                 } else {
                     $items[] = $blockInString->getId();
                 }
            }
            catch (Exception $exception) {}
        }

        return (bool)in_array($checkMeta ? ((string)$block->getId() . ":" . (string)$block->getDamage()) : $block->getId(), $items);
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
            if(strpos($string, "%") === false) {
                $item = Item::fromString($itemArgs[array_rand($itemArgs, 1)]);
            } else {
                $percentageData = [];
                foreach ($itemArgs as $itemName) {
                    if(($i = strpos($itemName, "%")) === false) {
                        $percentageData[$itemName] = 100;
                    } else {
                        $percentageData[substr($itemName, $i + 1)] = (int)substr($itemName, 0, $i);
                    }
                }

                shuffle($percentageData);

                do {
                    if(count($percentageData) == 0) {
                        $item = Item::fromString(array_key_last($percentageData));
                    } else {
                        $name = array_key_first($percentageData);
                        if(mt_rand(0, 100) < array_shift($percentageData)) {
                            $item = Item::fromString($name);
                        }
                    }
                }
                while($item === null);
            }
        }
        catch (Exception $exception) {
            $item = Item::get(Item::AIR);
        }

        if(!$item instanceof Item)
            return Block::get(Block::AIR);

        /** @var Block $block */
        $block = null;
        try {
            $block = $item->getBlock();
        }
        catch (Exception $exception) {
            $block = Block::get(Block::AIR);
        }

        if(!$block instanceof Block) {
            return Block::get(Block::AIR);
        }

        return $block;
    }
}