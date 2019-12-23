<?php

/**
 * Copyright (C) 2018-2019  CzechPMDevs
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
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\utils\Math;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Copier
 * @package buildertools\editors
 */
class Copier extends Editor {

    public const DIRECTION_PLAYER = 0;
    public const DIRECTION_UP = 1;
    public const DIRECTION_DOWN = 2;

    public const FLIP_DATA = [
        // stairs
        0 => [0 => 4, 1 => 5, 2 => 6, 3 => 7, 4 => 0, 5 => 1, 6 => 2, 7 => 3],
        // slabs
        1 => [0 => 8, 1 => 9, 2 => 10, 3 => 11, 4 => 12, 5 => 13, 6 => 14, 7 => 15, 8 => 0, 9 => 1, 10 => 2, 11 => 3, 12 => 4, 13 => 5, 14 => 6, 15 => 7]
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
     *
     * @return EditorResult
     */
    public function copy(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Player $player): EditorResult {
        $startTime = microtime(true);

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

        return new EditorResult($count, microtime(true)-$startTime, false);
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
            case "3:0":
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
            case "0:3":
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
        }
    }

    /**
     * @param Player $player
     */
    public function flip(Player $player) {
        if(!isset($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
                return;
        }

        $minY = null;
        $maxY = null;

        /**
         * @var Vector3 $vec
         */
        foreach ($this->copyData[$player->getName()]["data"] as [$vec]) {
            if($minY === null || $vec->getY() < $minY) {
                $minY = $vec->getY();
            }
            if($maxY === null || $vec->getY() > $maxY) {
                $maxY = $vec->getY();
            }
        }

        $add = (int)round(abs($maxY-$minY)/2);

        /**
         * @var Vector3 $vec
         * @var Block $block
         */
        foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
            $vec->setComponents($vec->getX(), (-$vec->getY())+$add, $vec->getZ());
            if(in_array($block->getId(), [Block::OAK_STAIRS, Block::COBBLESTONE_STAIRS, Block::ACACIA_STAIRS, Block::ACACIA_STAIRS, Block::DARK_OAK_STAIRS, Block::JUNGLE_STAIRS, Block::NETHER_BRICK_STAIRS, Block::PURPUR_STAIRS, Block::QUARTZ_STAIRS, Block::BRICK_STAIRS])) {
                $block->setDamage(self::FLIP_DATA[0][$block->getDamage()]);
            }
            if(in_array($block->getId(), [Block::STONE_SLAB, Block::STONE_SLAB2, Block::WOODEN_SLAB])) {
                $block->setDamage(self::FLIP_DATA[1][$block->getDamage()]);
            }
        }

        $player->sendMessage(BuilderTools::getPrefix()."§aSelected area flipped!");
    }

    /**
     * @param Player $player
     * @param int $pasteCount
     * @param int $mode
     */
    public function stack(Player $player, int $pasteCount, int $mode = Copier::DIRECTION_PLAYER) {
        if (!isset($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        $list = new BlockList();
        $list->setLevel($player->getLevel());

        /** @var Position $center */
        $center = $this->copyData[$player->getName()]["center"];
        $center = $center->add(1, 0 , 1); // why???

        switch ($mode) {
            case self::DIRECTION_PLAYER:
                $d = $player->getDirection();
                switch ($d) {
                    case 0:
                    case 2:
                        $minX = null;
                        $maxX = null;

                        /**
                         * @var Vector3 $vec
                         */
                        foreach ($this->copyData[$player->getName()]["data"] as [$vec]) {
                            if ($minX === null || $vec->getX() < $minX) {
                                $minX = $vec->getX();
                            }
                            if ($maxX === null || $vec->getX() > $maxX) {
                                $maxX = $vec->getX();
                            }
                        }

                        $length = (int)(round(abs($maxX - $minX))+1);
                        if ($d == 2) $length = -$length;
                        for ($pasted = 0; $pasted < $pasteCount; ++$pasted) {
                            $addX = $length * $pasted;
                            foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                                $list->addBlock($center->add($vec->add($addX)), $block);
                            }
                        }
                        break;
                    case 1:
                    case 3:
                        $minZ = null;
                        $maxZ = null;

                        /**
                         * @var Vector3 $vec
                         */
                        foreach ($this->copyData[$player->getName()]["data"] as [$vec]) {
                            if ($minZ === null || $vec->getZ() < $minZ) {
                                $minZ = $vec->getZ();
                            }
                            if ($maxZ === null || $vec->getZ() > $maxZ) {
                                $maxZ = $vec->getZ();
                            }
                        }

                        $length = (int)(round(abs($maxZ - $minZ))+1);
                        if ($d == 3) $length = -$length;
                        for ($pasted = 0; $pasted < $pasteCount; ++$pasted) {
                            $addZ = $length * $pasted;
                            foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                                $list->addBlock($center->add($vec->add(0, 0, $addZ)), $block);
                            }
                        }
                        break;
                }
                break;
            case self::DIRECTION_UP:
            case self::DIRECTION_DOWN:
                $minY = null;
                $maxY = null;

                /**
                 * @var Vector3 $vec
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec]) {
                    if ($minY === null || $vec->getY() < $minY) {
                        $minY = $vec->getY();
                    }
                    if ($maxY === null || $vec->getY() > $maxY) {
                        $maxY = $vec->getY();
                    }
                }

                $length = (int)(round(abs($maxY - $minY))+1);
                if ($mode == self::DIRECTION_DOWN) $length = -$length;
                for ($pasted = 0; $pasted <= $pasteCount; ++$pasted) {
                    $addY = $length * $pasted;
                    foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                        $list->addBlock($center->add($vec->add(0, $addY)), $block);
                    }
                }
                break;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(self::FILLER);
        $filler->fill($player, $list);
        $player->sendMessage(BuilderTools::getPrefix()."§aCopied area stacked!");
    }
}
