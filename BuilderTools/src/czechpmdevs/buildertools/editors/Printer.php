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
use czechpmdevs\buildertools\blockstorage\FastBlockMap;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\SingletonTrait;
use function abs;
use function microtime;

class Printer {
    use SingletonTrait;

    public const CUBE = 0x00;
    public const SPHERE = 0x01;
    public const CYLINDER = 0x02;
    public const HOLLOW_CUBE = 0x03;
    public const HOLLOW_SPHERE = 0x04;
    public const HOLLOW_CYLINDER = 0x05;

    public function draw(Player $player, Position $center, Block $block, int $brush = 4, int $mode = 0x00, bool $throwBlock = false): void {
        $undoList = new BlockArray();
        $undoList->setLevel($center->getLevel());
        $center = Math::ceilPosition($center);

        $level = $center->getLevelNonNull();

        $placeBlock = function (Vector3 $vector3) use ($level, $undoList, $block, $center, $throwBlock) {
            if($throwBlock) {
                $vector3 = $this->throwBlock(Position::fromObject($vector3, $center->getLevel()), $block);
            }
            if($vector3->getY() < 0) {
                return;
            }

            $fullBlock = $level->getBlock($vector3);
            $undoList->addBlock($vector3, $fullBlock->getId(), $fullBlock->getDamage());

            /** @phpstan-ignore-next-line */
            $level->setBlockIdAt($vector3->getX(), $vector3->getY(), $vector3->getZ(), $block->getId());
            /** @phpstan-ignore-next-line */
            $level->setBlockDataAt($vector3->getX(), $vector3->getY(), $vector3->getZ(), $block->getDamage());
        };

        if($mode == self::CUBE) {
            foreach (BlockGenerator::generateCube($brush) as [$x, $y, $z]) {
                $placeBlock($center->add($x, $y, $z));
            }
        } elseif($mode == self::SPHERE) {
            foreach (BlockGenerator::generateSphere($brush) as [$x, $y, $z]) {
                $placeBlock($center->add($x, $y, $z));
            }
        } elseif($mode == self::CYLINDER) {
            foreach (BlockGenerator::generateCylinder($brush, $brush) as [$x, $y, $z]) {
                $placeBlock($center->add($x, $y, $z));
            }
        } elseif($mode == self::HOLLOW_CUBE) {
            foreach (BlockGenerator::generateCube($brush, true) as [$x, $y, $z]) {
                $placeBlock($center->add($x, $y, $z));
            }
        } elseif($mode == self::HOLLOW_SPHERE) {
            foreach (BlockGenerator::generateSphere($brush, true) as [$x, $y, $z]) {
                $placeBlock($center->add($x, $y, $z));
            }
        } elseif($mode == self::HOLLOW_CYLINDER) {
            foreach (BlockGenerator::generateCylinder($brush, $brush,true) as [$x, $y, $z]) {
                $placeBlock($center->add($x, $y, $z));
            }
        }

        Canceller::getInstance()->addStep($player, $undoList);
    }

    private function throwBlock(Position $position, Block $block): Vector3 {
        $level = $position->getLevelNonNull();

        $x = $position->getFloorX();
        $y = $position->getFloorY();
        $z = $position->getFloorZ();

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        for(; $y >= 0 && $level->getBlockAt($x, $y, $z, true, false)->getId() == Block::AIR; $y--);

        $level->setBlockIdAt($x, $y, $z, $block->getId());
        $level->setBlockDataAt($x, $y, $z, $block->getDamage());

        return new Vector3($x, $y, $z);
    }

    public function makeSphere(Player $player, Position $center, int $radius, string $blocks, bool $hollow = false): EditorResult {
        $startTime = microtime(true);
        $center = Position::fromObject($center->ceil(), $center->getLevel());
        $radius = abs($radius);

        if($player->getY() - $radius < 0 || $player->getY() + $radius > Level::Y_MAX) {
            return EditorResult::error("Shape is outside of the map!");
        }

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);
        if(!$stringToBlockDecoder->isValid()) {
            return EditorResult::error("0 blocks found");
        }

        $floorX = $center->getFloorX();
        $floorY = $center->getFloorY();
        $floorZ = $center->getFloorZ();

        $mapper = new FastBlockMap();
        foreach (BlockGenerator::generateSphere($radius, $hollow) as [$x, $y, $z]) {
            $mapper->addBlock($floorX + $x, $floorY + $y, $floorZ + $z);
        }

        $fillSession = new FillSession($player->getLevelNonNull(), false);
        $fillSession->setDimensions($floorX - $radius, $floorX + $radius, $floorZ - $radius, $floorZ + $radius);

        foreach ($mapper->readBlocks() as [$x, $y, $z]) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $fillSession->setBlockAt($x, $y, $z, $id, $meta);
        }
        $fillSession->reloadChunks($player->getLevelNonNull());

        /** @var BlockArray $undoList */
        $undoList = $fillSession->getChanges();
        
        Canceller::getInstance()->addStep($player, $undoList);
        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
    }

    public function makeHollowSphere(Player $player, Position $center, int $radius, string $blocks): EditorResult {
        return $this->makeSphere($player, $center, $radius, $blocks, true);
    }

    public function makeCylinder(Player $player, Position $center, int $radius, int $height, string $blocks, bool $hollow = false): EditorResult {
        $startTime = microtime(true);
        $center = Position::fromObject($center->ceil(), $center->getLevel());

        if($player->getY() < 0 || $player->getY() + $height > Level::Y_MAX) {
            return EditorResult::error("Shape is outside of the map!");
        }

        $radius = abs($radius);

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);
        if(!$stringToBlockDecoder->isValid()) {
            return EditorResult::error("0 blocks found");
        }

        $floorX = $center->getFloorX();
        $floorY = $center->getFloorY();
        $floorZ = $center->getFloorZ();

        $mapper = new FastBlockMap();
        foreach (BlockGenerator::generateCylinder($radius, $height, $hollow) as [$x, $y, $z]) {
            $mapper->addBlock($floorX + $x, $floorY + $y, $floorZ + $z);
        }

        $fillSession = new FillSession($player->getLevelNonNull(), false);
        $fillSession->setDimensions($floorX - $radius, $floorX + $radius, $floorZ - $radius, $floorZ + $radius);

        foreach ($mapper->readBlocks() as [$x, $y, $z]) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $fillSession->setBlockAt($x, $y, $z, $id, $meta);
        }
        $fillSession->reloadChunks($player->getLevelNonNull());

        /** @var BlockArray $undoList */
        $undoList = $fillSession->getChanges();

        Canceller::getInstance()->addStep($player, $undoList);
        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
    }

    public function makeHollowCylinder(Player $player, Position $center, int $radius, int $height, string $blocks): EditorResult {
        return $this->makeCylinder($player, $center, $radius, $height, $blocks, true);
    }

    public function makePyramid(Player $player, Position $center, int $size, string $blocks, bool $hollow = false): EditorResult {
        $startTime = microtime(true);
        $center = Position::fromObject($center->ceil(), $center->getLevel());

        $size = abs($size);
        if($player->getY() < 0 || $player->getY() + $size > Level::Y_MAX) {
            return EditorResult::error("Shape is outside of the map!");
        }

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);
        if(!$stringToBlockDecoder->isValid()) {
            return EditorResult::error("0 blocks found");
        }

        $floorX = $center->getFloorX();
        $floorY = $center->getFloorY();
        $floorZ = $center->getFloorZ();

        $fillSession = new FillSession($player->getLevelNonNull(), false);
        $fillSession->setDimensions($floorX - $size, $floorX + $size, $floorZ - $size, $floorZ + $size);

        foreach (BlockGenerator::generatePyramid($size, $hollow) as [$x, $y, $z]) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $fillSession->setBlockAt($x + $floorX, $y + $floorY, $z + $floorZ, $id, $meta);
        }
        $fillSession->reloadChunks($player->getLevelNonNull());

        /** @var BlockArray $undoList */
        $undoList = $fillSession->getChanges();

        Canceller::getInstance()->addStep($player, $undoList);
        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
    }

    public function makeHollowPyramid(Player $player, Position $center, int $size, string $blocks): EditorResult {
        return $this->makePyramid($player, $center, $size, $blocks, true);
    }

    public function makeCube(Player $player, Position $center, int $radius, string $blocks, bool $hollow = false): EditorResult {
        $startTime = microtime(true);
        $center = Position::fromObject($center->ceil(), $center->getLevel());
        $radius = abs($radius);
        if($player->getY() - $radius < 0 || $player->getY() + $radius > Level::Y_MAX) {
            return EditorResult::error("Shape is outside of the map!");
        }

        $stringToBlockDecoder = new StringToBlockDecoder($blocks);
        if(!$stringToBlockDecoder->isValid()) {
            return EditorResult::error("0 blocks found");
        }

        $floorX = $center->getFloorX();
        $floorY = $center->getFloorY();
        $floorZ = $center->getFloorZ();

        $fillSession = new FillSession($player->getLevelNonNull(), false);
        $fillSession->setDimensions($floorX - $radius, $floorX + $radius, $floorZ - $radius, $floorZ + $radius);

        foreach (BlockGenerator::generateCube($radius, $hollow) as [$x, $y, $z]) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $fillSession->setBlockAt($x + $floorX, $y + $floorY, $z + $floorZ, $id, $meta);
        }
        $fillSession->reloadChunks($player->getLevelNonNull());

        /** @var BlockArray $undoList */
        $undoList = $fillSession->getChanges();

        Canceller::getInstance()->addStep($player, $undoList);
        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
    }

    public function makeHollowCube(Player $player, Position $center, int $radius, string $blocks): EditorResult {
        return $this->makeCube($player, $center, $radius, $blocks, true);
    }
}