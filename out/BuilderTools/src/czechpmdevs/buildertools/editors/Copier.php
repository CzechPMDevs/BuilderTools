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

use czechpmdevs\buildertools\blockstorage\SelectionData;
use czechpmdevs\buildertools\blockstorage\UpdateLevelData;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\utils\RotationUtil;
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

    /** @var SelectionData[] $copiedClipboards */
    public $copiedClipboards = [];

    /**
     * @return string $copier
     */
    public function getName(): string {
        return "Copier";
    }

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param Player $player
     *
     * @return EditorResult
     */
    public function copy(Vector3 $pos1, Vector3 $pos2, Player $player): EditorResult {
        $startTime = microtime(true);

        $clipboard = $this->copiedClipboards[$player->getName()] = new SelectionData();
        $clipboard->setPlayerPosition($player->ceil());

        $i = 0;
        foreach (BlockGenerator::fillCuboid($pos1, $pos2) as [$x, $y, $z]) {
            $blockPos = new Vector3($x, $y, $z);

            $block = $player->getLevel()->getBlock($blockPos);
            $clipboard->addBlock($blockPos->subtract($clipboard->getPlayerPosition())->ceil(), $block->getId(), $block->getDamage());

            $i++;
        }

        return new EditorResult($i, microtime(true)-$startTime, false);
    }

    /**
     * @param Player $player
     */
    public function merge(Player $player) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->merge($player, UpdateLevelData::fromBlockArray($this->copiedClipboards[$player->getName()]->addVector3($player->ceil())));
    }

    /**
     * @param Player $player
     */
    public function paste(Player $player) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->fill($player, UpdateLevelData::fromBlockArray($this->copiedClipboards[$player->getName()]->addVector3($player->ceil())));
    }

    /**
     * @param Player $player
     * @param int $axis
     * @param int $rotation
     */
    public function rotate(Player $player, int $axis, int $rotation) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        $this->copiedClipboards[$player->getName()] = RotationUtil::rotate($this->copiedClipboards[$player->getName()], $axis, $rotation);
    }

    /**
     * @param Player $player
     * @param int $pasteCount
     * @param int $mode
     */
    public function stack(Player $player, int $pasteCount, int $mode = Copier::DIRECTION_PLAYER) {
        if (!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        $clipboard = $this->copiedClipboards[$player->getName()];

        $updateData = new UpdateLevelData();
        $updateData->setLevel($clipboard->getLevel());

        $center = $clipboard->getPlayerPosition()->ceil(); // Why there were + vec(1, 0, 1)
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
                            foreach ($clipboard->read(false) as [$x, $y, $z, $id, $meta]) {
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
                            foreach ($clipboard->read(false) as [$x, $y, $z, $id, $meta]) {
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
                    foreach ($clipboard->read() as [$x, $y, $z, $id, $meta]) {
                        $updateData->addBlock($center->add($x, $y + $addY, $z), $id, $meta);
                    }
                }
                break;
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(self::FILLER);
        $filler->fill($player, $updateData);
        $player->sendMessage(BuilderTools::getPrefix()."§aCopied area stacked!");
    }
}
