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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\BlockList;
use czechpmdevs\buildertools\utils\Math;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\format\EmptySubChunk;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Copier
 * @package buildertools\editors
 */
class Copier extends Editor {

    public const FLIP_DATA = [
        // stairs
        0 => [
            0 => 4, 1 => 5, 2 => 6, 3 => 7, 4 => 0, 5 => 1, 6 => 2, 7 => 3
        ],
        // slabs
        1 => [
            0 => 8, 1 => 9, 2 => 10, 3 => 11, 4 => 12, 5 => 13, 6 => 14, 7 => 15, 8 => 0, 9 => 1, 10 => 2, 11 => 3, 12 => 4, 13 => 5, 14 => 6, 15 => 7
        ]
    ];

    /** @var array $copyData */
    public $copyData = [];

    /**
     * @return string $copier
     */
    public function getName(): string {
        return "Copier";
    }

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     * @param Player $player
     */
    public function copy(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Player $player) {
        $this->copyData[$player->getName()] = [
            "data" => [],
            "center" => $player->asPosition(),
            "direction" => $player->getDirection(),
            "rotated" => false
        ];
        $count = 0;
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $this->copyData[$player->getName()]["data"][$count] = [($vec = Math::roundVector3(new Vector3($x, $y, $z)))->subtract(Math::roundVector3($player->asVector3())), $player->getLevel()->getBlock($vec)];
                    $count++;
                }
            }
        }
        $player->sendMessage(BuilderTools::getPrefix()."§a{$count} blocks copied to clipboard! Use //paste to paste");
    }

    /**
     * @param Player $player
     */
    public function merge(Player $player) {
        if(!isset($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        /** @var array $blocks */
        $blocks = $this->copyData[$player->getName()]["data"];

        $list = new BlockList();
        $list->setLevel($player->getLevel());

        /**
         * @var Vector3 $vec
         * @var Block $block
         */
        foreach ($blocks as [$vec, $block]) {
            if($player->getLevel()->getBlock($vec->add($player->asVector3()))->getId() == 0) {
                $list->addBlock($vec->add($player->asVector3()), $block);
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->fill($player, $list);
    }

    /**
     * @param Player $player
     */
    public function paste(Player $player) {
        if(!isset($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix()."§cUse //copy first!");
            return;
        }

        /** @var array $blocks */
        $blocks = $this->copyData[$player->getName()]["data"];

        $list = new BlockList();
        $list->setLevel($player->getLevel());

        /**
         * @var Vector3 $vec
         * @var Block $block
         */
        foreach ($blocks as [$vec, $block]) {
            $list->addBlock($vec->add($player->asVector3()), $block);
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->fill($player, $list);
    }

    /**
     * @param Player $player
     */
    public function addToRotate(Player $player) {
        if(!isset($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix()."§cUse //copy first!");
            return;
        }
        if($this->copyData[$player->getName()]["rotated"] == true) {
            $player->sendMessage(BuilderTools::getPrefix()."§cSelected area is already rotated!");
            return;
        }
        $player->sendMessage(BuilderTools::getPrefix()."Select direction to rotate moving.");
        BuilderTools::getListener()->directionCheck[$player->getName()] = intval($player->getDirection());
    }

    /**
     * @param Player $player
     * @param int $fromDirection
     * @param int $toDirection
     */
    public function rotate(Player $player, int $fromDirection, int $toDirection) {
        $this->copyData[$player->getName()]["rotated"] = true;
        $min = min($fromDirection, $toDirection);
        $max = max($fromDirection, $toDirection);

        if($min == $max) {
            $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated!");
            return;
        }

        $id = "{$fromDirection}:{$toDirection}";

        switch ($id) {
            case "0:0":
            case "1:1":
            case "2:2":
            case "3:3":
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "0:1":
            case "1:2":
            case "2:3":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    $vec->setComponents($vec->getZ(), $vec->getY(), $vec->getX());
                }
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "0:2":
            case "1:3":
            case "2:0":
            case "3:1":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    $vec->setComponents(-$vec->getX(), $vec->getY(), -$vec->getZ());
                }
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "1:0":
            case "2:1":
            case "3:2":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    $vec->setComponents(-$vec->getX(), $vec->getY(), -$vec->getZ());
                }
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    $vec->setComponents($vec->getZ(), $vec->getY(), $vec->getX());
                }

                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "3:0":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    $vec->setComponents(-$vec->getX(), $vec->getY(), -$vec->getZ());
                }
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;
        }
    }

    /**
     * @param Player $player
     *./
    public function flip(Player $player) {
        /**
         * @var Vector3 $vec
         * @var Block $block
         *./
        foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
            $vec->setComponents($vec->getX(), -$vec->getY(), $vec->getZ());
            if(in_array($block->getId(), [Block::OAK_STAIRS, Block::COBBLESTONE_STAIRS, Block::ACACIA_STAIRS, Block::ACACIA_STAIRS, Block::DARK_OAK_STAIRS, Block::JUNGLE_STAIRS, Block::NETHER_BRICK_STAIRS, Block::PURPUR_STAIRS, Block::QUARTZ_STAIRS, Block::BRICK_STAIRS])) {
                $block->setDamage(self::FLIP_DATA[0][$block->getDamage()]);
            }
            if(in_array($block->getId(), [Block::STONE_SLAB, Block::STONE_SLAB2, Block::WOODEN_SLAB])) {
                $block->setDamage(self::FLIP_DATA[1][$block->getDamage()]);
            }
        }

        $player->sendMessage(BuilderTools::getPrefix()."§aSelected area flipped!");
    }*/

    /**
     * @param Player $player
     */
    public function flip(Player $player) {
        $list = BlockList::fromCopyData($this->copyData[$player->getName()]["data"], true);
        /** @var int $minY */
        $minY = null;
        /** @var int $maxY */
        $maxY = null;

        // b = block :D
        foreach ($list->getBlockMap() as $x => $yzb) {
            foreach ($yzb as $y => $zb) {
                if($minY === null || $minY > $y) $minY = $y;
                if($maxY === null || $maxY < $y) $maxY = $y;
            }
        }

        $middleY = $minY+((int)round(($maxY-$minY)/2));
        $middleExist = is_int(($maxY-$minY)/2);

        $fillList = new BlockList();

        foreach ($list->getAll() as $block) {
            if($middleExist && $block->getY() == $middleY) {
                $fillList->addBlock($block->asVector3(), $block);
            }
            elseif($block->getY() < $middleY) {
                if($middleExist)
                    $fillList->addBlock(new Vector3($block->getX(), $middleY+abs($middleY-$block->getY()), $block->getZ()), $block);
                else
                    $fillList->addBlock(new Vector3($block->getX(), ($middleY-1)+abs(($middleY-1)-$block->getY())+1, $block->getZ()), $block);
            }
            // děláno na rychlo
            else {
                if($middleExist)
                    $fillList->addBlock(new Vector3($block->getX(), $middleY-abs($middleY-$block->getY()), $block->getZ()), $block);
                else
                    $fillList->addBlock(new Vector3($block->getX(), ($middleY-(abs($middleY-$block->getY()))-1), $block->getZ()), $block);
            }
        }

        $fixBlock = function (Block $block): Block {
            if(in_array($block->getId(), [Block::OAK_STAIRS, Block::COBBLESTONE_STAIRS, Block::ACACIA_STAIRS, Block::ACACIA_STAIRS, Block::DARK_OAK_STAIRS, Block::JUNGLE_STAIRS, Block::NETHER_BRICK_STAIRS, Block::PURPUR_STAIRS, Block::QUARTZ_STAIRS, Block::BRICK_STAIRS])) {
                $block->setDamage(self::FLIP_DATA[0][$block->getDamage()]);
            }
            if(in_array($block->getId(), [Block::STONE_SLAB, Block::STONE_SLAB2, Block::WOODEN_SLAB])) {
                $block->setDamage(self::FLIP_DATA[1][$block->getDamage()]);
            }
            return $block;
        };

        $this->copyData[$player->getName()]["data"] = [];
        foreach ($fillList->getAll() as $block) {
            $this->copyData[$player->getName()]["data"][] = [$block->asVector3(), $fixBlock($block)];
        }

        $player->sendMessage(BuilderTools::getPrefix() . "Selected area flipped!");
    }
}