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
use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\editors\blockstorage\ClipboardData;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\math\RotationUtil;
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

    /** @var ClipboardData[] $copiedClipboards */
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

        $clipboard = $this->copiedClipboards[$player->getName()] = new ClipboardData($player);
        $clipboard->setPlayerPosition(Math::roundVector3($player->asVector3()));

        $i = 0;
        foreach (BlockGenerator::generateCuboid($pos1, $pos2) as [$x, $y, $z]) {
            $blockPos = new Vector3($x, $y, $z);

            $block = $player->getLevel()->getBlock($blockPos);
            $clipboard->addBlock(Math::roundVector3($blockPos->subtract($clipboard->getPlayerPosition())), $block);

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

        $center = Math::roundVector3($player->asVector3());
        /** @var array $blocks */
        $blocks = [];

        foreach ($this->copiedClipboards[$player->getName()]->getAll() as $blockInClipboard) {
            if($blockInClipboard->getId() !== 0) {
                $block = clone $blockInClipboard;
                $pos = $block->add($center);
                $block->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
                $blocks[] = $block;
            }
        }

        $list = new BlockList();
        $list->setLevel($player->getLevel());
        $list->setAll($blocks);

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->fill($player, $list);
    }

    /**
     * @param Player $player
     */
    public function paste(Player $player) {
        if(!isset($this->copiedClipboards[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
            return;
        }

        $center = Math::roundVector3($player->asVector3());
        /** @var array $blocks */
        $blocks = [];

        foreach ($this->copiedClipboards[$player->getName()]->getAll() as $blockInClipboard) {
            $block = clone $blockInClipboard;
            $pos = $block->add($center);
            $block->setComponents($pos->getX(), $pos->getY(), $pos->getZ());
            $blocks[] = $block;
        }

        $list = new BlockList();
        $list->setLevel($player->getLevel());
        $list->setAll($blocks);

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $filler->fill($player, $list);
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

        $list = RotationUtil::rotate($this->copiedClipboards[$player->getName()], $axis, $rotation);
        $this->copiedClipboards[$player->getName()]->setAll($list->getAll());
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

        $list = new BlockList();
        $list->setLevel($player->getLevel());

        $center = Math::roundVector3($clipboard->getPlayerPosition()->add(1, 0, 1)); // why add???

        switch ($mode) {
            case self::DIRECTION_PLAYER:
                $d = $player->getDirection();
                switch ($d) {
                    case 0:
                    case 2:
                        $minX = null;
                        $maxX = null;

                        $metadata = $clipboard->getMetadata();
                        $minX = $metadata->minX;
                        $maxX = $metadata->maxX;

                        $length = (int)(round(abs($maxX - $minX)) + 1);
                        if ($d == 2) $length = -$length;

                        for ($pasted = 0; $pasted < $pasteCount; ++$pasted) {
                            $addX = $length * $pasted;
                            foreach ($clipboard->getAll() as $block) {
                                $list->addBlock($center->add($block->add($addX)), $block);
                            }
                        }
                        break;
                    case 1:
                    case 3:
                        $minZ = null;
                        $maxZ = null;

                        $metadata = $clipboard->getMetadata();
                        $minZ = $metadata->minZ;
                        $maxZ = $metadata->maxZ;

                        $length = (int)(round(abs($maxZ - $minZ)) + 1);
                        if ($d == 3) $length = -$length;

                        for ($pasted = 0; $pasted < $pasteCount; ++$pasted) {
                            $addZ = $length * $pasted;
                            foreach ($clipboard->getAll() as $block) {
                                $list->addBlock($center->add($block->add(0, 0, $addZ)), $block);
                            }
                        }
                        break;
                }
                break;
            case self::DIRECTION_UP:
            case self::DIRECTION_DOWN:
                $minY = null;
                $maxY = null;

                $metadata = $clipboard->getMetadata();
                $minY = $metadata->minY;
                $maxY = $metadata->maxY;

                $length = (int)(round(abs($maxY - $minY))+1);
                if ($mode == self::DIRECTION_DOWN) $length = -$length;

                for ($pasted = 0; $pasted <= $pasteCount; ++$pasted) {
                    $addY = $length * $pasted;
                    foreach ($clipboard->getAll() as $block) {
                        $list->addBlock($center->add($block->add(0, $addY)), $block);
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
