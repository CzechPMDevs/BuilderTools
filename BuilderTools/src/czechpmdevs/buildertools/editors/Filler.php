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
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\Player;

/**
 * Class Filler
 * @package buildertools\editors
 */
class Filler extends Editor {

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param Level $level
     * @param string $blockArgs
     * @param bool $filled
     *
     * @return BlockList
     */
    public function prepareFill(Vector3 $pos1, Vector3 $pos2, Level $level, string $blockArgs, $filled = true): BlockList {
        $blockList = new BlockList;
        $blockList->setLevel($level);

        foreach (BlockGenerator::generateCuboid($pos1, $pos2) as [$x, $y, $z]) {
            if(!$filled) {
                if($x != min($pos1->getX(), $pos2->getX()) && $x != max($pos1->getX(), $pos2->getX())) {
                    if($y != min($pos1->getY(), $pos2->getY()) && $y != max($pos1->getY(), $pos2->getY())) {
                        if($z != min($pos1->getZ(), $pos2->getZ()) && $z != max($pos1->getZ(), $pos2->getZ())) {
                            continue;
                        }
                    }
                }
            }

            $blockList->addBlock(new Vector3($x, $y, $z), $this->getBlockFromString($blockArgs));
        }

        return $blockList;
    }


    /**
     * @param Player $player
     * @param BlockList $blockList
     * @param bool[] $settings
     *
     * @return EditorResult
     */
    public function fill(Player $player, BlockList $blockList, array $settings = []): EditorResult {
        $startTime = microtime(true);
        /** @var  $blocks */
        $blocks = $blockList->getAll();

        /** @var bool $saveUndo */
        $saveUndo = true;
        /** @var bool $saveRedo */
        $saveRedo = false;

        if(isset($settings["saveUndo"]) && is_bool($settings["saveUndo"])) $saveUndo = $settings["saveUndo"];
        if(isset($settings["saveRedo"]) && is_bool($settings["saveRedo"])) $saveRedo = $settings["saveRedo"];

        $undoList = new BlockList;
        $redoList = new BlockList;

        if($saveUndo) $undoList->setLevel($blockList->getLevel());
        if($saveRedo) $redoList->setLevel($blockList->getLevel());

        $iterator = new SubChunkIteratorManager($blockList->getLevel());

        /** @var int $minX */
        $minX = null;
        /** @var int $maxX */
        $maxX = null;
        /** @var int $minZ */
        $minZ = null;
        /** @var int $maxZ */
        $maxZ = null;

        /**
         * @param Level $level
         * @param int $x1
         * @param int $z1
         * @param $x2
         * @param $z2
         */
        $reloadChunks = function (Level $level, int $x1, int $z1, int $x2, int $z2) {
            for($x = $x1 >> 4; $x <= $x2 >> 4; $x++) {
                for($z = $z1 >> 4; $z <= $z2 >> 4; $z++) {
                    $tiles = $level->getChunkTiles($x, $z);
                    $chunk = $level->getChunk($x, $z);
                    $level->setChunk($x, $z, $chunk);

                    foreach ($tiles as $tile) {
                        $tile->closed = false;
                        $tile->setLevel($level);
                        $level->addTile($tile);
                    }


                    foreach ($level->getChunkLoaders($x, $z) as $chunkLoader) {
                        if($chunkLoader instanceof Player) {
                            if(class_exists(FullChunkDataPacket::class)) {
                                $pk = new FullChunkDataPacket();
                                $pk->chunkX = $x;
                                $pk->chunkZ = $z;
                                $pk->data = $chunk->networkSerialize();
                            }
                            else {
                                $pk = LevelChunkPacket::withoutCache($x, $z, $chunk->getSubChunkSendCount(), $chunk->networkSerialize());
                            }
                            $chunkLoader->dataPacket($pk);
                        }
                    }
                }
            }
        };

        foreach ($blocks as $block) {
            // min and max positions
            if($minX === null || $block->getX() < $minX) $minX = $block->getX();
            if($minZ === null || $block->getZ() < $minZ) $minZ = $block->getZ();
            if($maxX === null || $block->getX() > $maxX) $maxX = $block->getX();
            if($maxZ === null || $block->getZ() > $maxZ) $maxZ = $block->getZ();

            $iterator->moveTo((int)$block->getX(), (int)$block->getY(), (int)$block->getZ());

            if($iterator->currentSubChunk === null) {
                $this->getPlugin()->getLogger()->error("Error while filling: Could not found sub chunk at {$block->getX()}:{$block->getY()}:{$block->getZ()}");
                continue;
            }

            if($saveUndo) $undoList->addBlock($block->asVector3(), Block::get($iterator->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f)));
            if($saveRedo) $redoList->addBlock($block->asVector3(), Block::get($iterator->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f)));
            $iterator->currentSubChunk->setBlock($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f, $block->getId(), $block->getDamage());
        }

        $reloadChunks($blockList->getLevel(), (int)$minX, (int)$minZ, (int)$maxX, (int)$maxZ);

        if($saveUndo) {
            /** @var Canceller $canceller */
            $canceller = BuilderTools::getEditor(static::CANCELLER);
            $canceller->addStep($player, $undoList);
        }

        if($saveRedo) {
            /** @var Canceller $canceller */
            $canceller = BuilderTools::getEditor(static::CANCELLER);
            $canceller->addRedo($player, $redoList);
        }


        return new EditorResult(count($blocks), microtime(true)-$startTime);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Filler";
    }
}