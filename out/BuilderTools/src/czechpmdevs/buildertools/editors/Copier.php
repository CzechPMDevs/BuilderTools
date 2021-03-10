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

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\SelectionData;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\utils\RotationUtil;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Copier extends Editor {

    public const DIRECTION_PLAYER = 0;
    public const DIRECTION_UP = 1;
    public const DIRECTION_DOWN = 2;

    /** @var SelectionData[] */
    public $copiedClipboards = [];

    public function getName(): string {
        return "Copier";
    }

    public function copy(Vector3 $pos1, Vector3 $pos2, Player $player): EditorResult {
        $startTime = microtime(true);

        $clipboard = (new SelectionData())->setPlayerPosition($player->ceil());

        $i = 0;
        foreach (BlockGenerator::fillCuboid($pos1, $pos2) as $blockPos) {
            $block = $player->getLevel()->getBlock($blockPos);
            $clipboard->addBlock($blockPos, $block->getId(), $block->getDamage());

            $i++;
        }

        $this->copiedClipboards[$player->getName()] = $clipboard;

        return new EditorResult($i, microtime(true)-$startTime, false);
    }

    public function merge(Player $player) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->merge($player, $this->copiedClipboards[$player->getName()]->addVector3($player->ceil()->subtract($this->copiedClipboards[$player->getName()]->getPlayerPosition()))->setLevel($player->getLevel()));
    }

    public function paste(Player $player) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->fill($player, $this->copiedClipboards[$player->getName()]->addVector3($player->ceil()->subtract($this->copiedClipboards[$player->getName()]->getPlayerPosition()))->setLevel($player->getLevel()));
    }

    public function rotate(Player $player, int $axis, int $rotation) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        $this->copiedClipboards[$player->getName()] = RotationUtil::rotate($this->copiedClipboards[$player->getName()], $axis, $rotation);
    }

    public function stack(Player $player, int $pasteCount, int $mode = Copier::DIRECTION_PLAYER) {
        if (!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        $clipboard = $this->copiedClipboards[$player->getName()];

        $updateData = new BlockArray();
        $updateData->setLevel($player->getLevel());

        $center = $clipboard->getPlayerPosition()->ceil(); // Why there was +vec(1, 0, 1)?
        switch ($mode) {
            case self::DIRECTION_PLAYER:
                $d = $player->getDirection();
                switch ($d) {
                    case 0:
                    case 2:
                        $metadata = $clipboard->getSizeData();
                        $minX = $metadata->minX;
                        $maxX = $metadata->maxX;

                        $length = (int)(round(abs($maxX - $minX)) + 1);
                        if ($d == 2) $length = -$length;

                        for ($pasted = 0; $pasted < $pasteCount; ++$pasted) {
                            $addX = $length * $pasted;
                            while ($clipboard->hasNext()) {
                                $clipboard->readNext($x, $y, $z, $id, $meta);
                                $updateData->addBlock($center->add($x + $addX, $y, $z), $id, $meta);
                            }
                        }
                        break;
                    case 1:
                    case 3:
                        $metadata = $clipboard->getSizeData();
                        $minZ = $metadata->minZ;
                        $maxZ = $metadata->maxZ;

                        $length = (int)(round(abs($maxZ - $minZ)) + 1);
                        if ($d == 3) $length = -$length;

                        for ($pasted = 0; $pasted < $pasteCount; ++$pasted) {
                            $addZ = $length * $pasted;
                            while ($clipboard->hasNext()) {
                                $clipboard->readNext($x, $y, $z, $id, $meta);
                                $updateData->addBlock($center->add($x, $y, $z + $addZ), $id, $meta);
                            }
                        }
                        break;
                }
                break;
            case self::DIRECTION_UP:
            case self::DIRECTION_DOWN:
                $metadata = $clipboard->getSizeData();
                $minY = $metadata->minY;
                $maxY = $metadata->maxY;

                $length = (int)(round(abs($maxY - $minY))+1);
                if ($mode == self::DIRECTION_DOWN) $length = -$length;

                for ($pasted = 0; $pasted <= $pasteCount; ++$pasted) {
                    $addY = $length * $pasted;
                    while ($clipboard->hasNext()) {
                        $clipboard->readNext($x, $y, $z, $id, $meta);
                        $updateData->addBlock($center->add($x, $y + $addY, $z), $id, $meta);
                    }
                }
                break;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(self::FILLER);
        $filler->fill($player, $updateData);
        $player->sendMessage(BuilderTools::getPrefix() . "§aCopied area stacked!");
    }

    public function move(Vector3 $pos1, Vector3 $pos2, Vector3 $add, Player $player) {
        $blocks = new BlockArray(true);
        $blocks->setLevel($player->getLevel());

        // Old blocks (to remove)
        $blockPositions = [];

        // Add new blocks
        /** @var Vector3 $vector3 */
        foreach (BlockGenerator::fillCuboid($pos1, $pos2) as $vector3) {
            if(($block = $player->getLevel()->getBlock($vector3))->getId() != 0) {
                $blockPositions[] = Level::blockHash($vector3->getX(), $vector3->getY(), $vector3->getZ());
                $blocks->addBlock($add->add($vector3), $block->getId(), $block->getDamage());
            }
        }

        // Remove old blocks
        foreach ($blockPositions as $hash) {
            Level::getBlockXYZ($hash, $x, $y, $z);

            $blocks->addBlock(new Vector3($x, $y, $z), 0, 0);
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(self::FILLER);
        $filler->fill($player, $blocks, true, false, true);
    }
}
