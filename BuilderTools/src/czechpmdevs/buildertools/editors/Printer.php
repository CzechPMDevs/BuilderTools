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
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Printer extends Editor {

    public const CUBE = 0x00;
    public const SPHERE = 0x01;
    public const CYLINDER = 0x02;
    public const HOLLOW_CUBE = 0x03;
    public const HOLLOW_SPHERE = 0x04;
    public const HOLLOW_CYLINDER = 0x05;

    public function draw(Player $player, Position $center, Block $block, int $brush = 4, int $mode = 0x00, bool $throwBlock = false) {
        $undoList = new BlockArray();
        $undoList->setLevel($center->getLevel());
        $center = Math::ceilPosition($center);

        $placeBlock = function (Vector3 $vector3) use ($undoList, $block, $center, $throwBlock) {
            if($throwBlock) {
                $vector3 = $this->throwBlock(Position::fromObject($vector3, $center->getLevel()), $block);
            }
            if($vector3->getY() < 0) {
                return;
            }

            $fullBlock = $center->getLevel()->getBlock($vector3);
            $undoList->addBlock($vector3, $fullBlock->getId(), $fullBlock->getDamage());
            $center->getLevel()->setBlockIdAt($vector3->getX(), $vector3->getY(), $vector3->getZ(), $block->getId());
            $center->getLevel()->setBlockDataAt($vector3->getX(), $vector3->getY(), $vector3->getZ(), $block->getDamage());
        };

        if($mode == self::CUBE) {
            foreach (BlockGenerator::generateCube($brush) as $vector3) {
                $placeBlock($center->add($vector3));
            }
        } elseif($mode == self::SPHERE) {
            foreach (BlockGenerator::generateSphere($brush) as $vector3) {
                $placeBlock($center->add($vector3));
            }
        } elseif($mode == self::CYLINDER) {
            foreach (BlockGenerator::generateCylinder($brush, $brush) as $vector3) {
                $placeBlock($center->add($vector3));
            }
        } elseif($mode == self::HOLLOW_CUBE) {
            foreach (BlockGenerator::generateCube($brush, true) as $vector3) {
                $placeBlock($center->add($vector3));
            }
        } elseif($mode == self::HOLLOW_SPHERE) {
            foreach (BlockGenerator::generateSphere($brush, true) as $vector3) {
                $placeBlock($center->add($vector3));
            }
        } elseif($mode == self::HOLLOW_CYLINDER) {
            foreach (BlockGenerator::generateCylinder($brush, $brush,true) as $vector3) {
                $placeBlock($center->add($vector3));
            }
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor(Editor::CANCELLER);
        $canceller->addStep($player, $undoList);
    }

    private function throwBlock(Position $position, Block $block): Vector3 {
        $level = $position->getLevel();

        $x = $position->getX();
        $y = $position->getY();
        $z = $position->getZ();

        $finalY = $y;

        for($a = $y+1; $a > 0 && $level->getBlockAt($x, $a-1, $z)->getId() == Block::AIR; $a--) {
            $finalY = $a-1;
        }

        $level->setBlockIdAt($x, $finalY, $z, $block->getId());
        $level->setBlockDataAt($x, $finalY, $z, $block->getDamage());

        return new Vector3($x, $finalY, $z);
    }

    public function makeSphere(Player $player, Position $center, int $radius, string $blocks, bool $hollow = false): EditorResult {
        $center = Position::fromObject($center->ceil()->add(-1, 0, -1), $center->getLevel());

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);

        $updateLevelData = new BlockArray(true);
        $updateLevelData->setLevel($center->getLevel());

        $radius = abs($radius);
        foreach (BlockGenerator::generateSphere($radius, $hollow) as $vector3) {
            if($vector3->getY() < 0 || $vector3->getY() > 255) {
                continue;
            }

            $stringToBlockDecoder->nextBlock($id, $meta);
            $updateLevelData->addBlock($center->add($vector3), $id, $meta);
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $updateLevelData);
    }

    public function makeHollowSphere(Player $player, Position $center, int $radius, string $blocks): EditorResult {
        return $this->makeSphere($player, $center, $radius, $blocks, true);
    }

    public function makeCylinder(Player $player, Position $center, int $radius, int $height, string $blocks, bool $hollow = false): EditorResult {
        $center = Position::fromObject($center->ceil()->add(-1, 0, -1), $center->getLevel());

        $stringToBlocksDecoder = new StringToBlockDecoder($blocks);

        $updateLevelData = new BlockArray();
        $updateLevelData->setLevel($center->getLevel());

        if($height == 0) {
            return new EditorResult(0, 0, true);
        }

        if($height < 0) {
            $center->setComponents($center->getX(), $center->getY() + $height, $center->getZ());
            $height = -$height;
        }

        $radius = abs($radius);
        foreach (BlockGenerator::generateCylinder($radius, $height, $hollow) as $vector3) {
            $stringToBlocksDecoder->nextBlock($id, $meta);
            $updateLevelData->addBlock($center->add($vector3), $id, $meta);
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $updateLevelData);
    }

    public function makeHollowCylinder(Player $player, Position $center, int $radius, int $height, string $blocks): EditorResult {
        return $this->makeCylinder($player, $center, $radius, $height, $blocks, true);
    }

    public function makePyramid(Player $player, Position $center, int $size, string $blocks, bool $hollow = false): EditorResult {
        $center = Position::fromObject($center->ceil()->add(-1, 0, -1), $center->getLevel());

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);

        $updateLevelData = new BlockArray();
        $updateLevelData->setLevel($center->getLevel());

        foreach (BlockGenerator::generatePyramid($size, $hollow) as $vector3) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $updateLevelData->addBlock($center->add($vector3), $id, $meta);
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $updateLevelData);
    }

    public function makeHollowPyramid(Player $player, Position $center, int $size, string $blocks): EditorResult {
        return $this->makePyramid($player, $center, $size, $blocks, true);
    }

    public function makeCube(Player $player, Position $center, int $radius, string $blocks, bool $hollow = false): EditorResult {
        $center = Math::ceilPosition($center);

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);

        $updateLevelData = new BlockArray();
        $updateLevelData->setLevel($center->getLevel());

        foreach (BlockGenerator::generateCube($radius, $hollow) as $vector3) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $updateLevelData->addBlock($center->add($vector3), $id, $meta);
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        return $filler->fill($player, $updateLevelData);
    }

    public function makeHollowCube(Player $player, Position $center, int $radius, string $blocks): EditorResult {
        return $this->makeCube($player, $center, $radius, $blocks, true);
    }

    public function getName(): string {
        return "Printer";
    }
}