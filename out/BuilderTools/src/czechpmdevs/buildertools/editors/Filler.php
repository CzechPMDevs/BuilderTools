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
use czechpmdevs\buildertools\blockstorage\UpdateLevelData;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use pocketmine\block\BlockIds;
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
     * @return BlockArray
     */
    public function prepareFill(Vector3 $pos1, Vector3 $pos2, Level $level, string $blockArgs, $filled = true): BlockArray {
        $updates = new BlockArray();
        $updates->setLevel($level);

        foreach (BlockGenerator::fillCuboid($pos1, $pos2, !$filled) as $vector3) {
            $updates->addBlock($vector3, ...$this->getBlockArgsFromString($blockArgs));
        }

        return $updates;
    }


    /**
     * @param Player $player
     * @param UpdateLevelData $updateData
     * @param bool $saveUndo
     * @param bool $saveRedo
     * @param bool $replaceOnlyAir
     *
     * @return EditorResult
     */
    public function fill(Player $player, UpdateLevelData $updateData, bool $saveUndo = true, bool $saveRedo = false, bool $replaceOnlyAir = false): EditorResult {
        $startTime = microtime(true);
        $count = $updateData->size();

        $undoArray = $saveUndo ? (new BlockArray())->setLevel($updateData->getLevel()) : null;
        $redoArray = $saveRedo ? (new BlockArray())->setLevel($updateData->getLevel()) : null;

        $iterator = new SubChunkIteratorManager($updateData->getLevel());

        /** @var int|null $minX */
        $minX = null;
        /** @var int|null $maxX */
        $maxX = null;
        /** @var int|null $minZ */
        $minZ = null;
        /** @var int|null $maxZ */
        $maxZ = null;

        $x = $y = $z = $id = $meta = null;
        while ($updateData->hasNext()) {
            $updateData->readNext($x, $y, $z, $id, $meta);

            // min and max positions
            if($minX === null || $x < $minX) $minX = $x;
            if($minZ === null || $z < $minZ) $minZ = $z;
            if($maxX === null || $x > $maxX) $maxX = $x;
            if($maxZ === null || $z > $maxZ) $maxZ = $z;

            $iterator->moveTo((int)$x, (int)$y, (int)$z);

            if($iterator->currentSubChunk === null) {
                $iterator->currentSubChunk = $iterator->level->getChunk($x >> 4, $z >> 4)->getSubChunk($y >> 4, true);
                $this->getPlugin()->getLogger()->error("Error while filling: Could not find sub chunk at {$x}:{$y}:{$z}");
                continue;
            }

            if($replaceOnlyAir) {
                if($iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) != BlockIds::AIR) {
                    continue;
                }
            }

            if($saveUndo)
                $undoArray->addBlock(new Vector3($x, $y, $z), $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
            if($saveRedo)
                $redoArray->addBlock(new Vector3($x, $y, $z), $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));

            $iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id, $meta);
        }

        $this->reloadChunks($player->getLevel(), (int)$minX, (int)$minZ, (int)$maxX, (int)$maxZ);

        if($saveUndo) {
            /** @var Canceller $canceller */
            $canceller = BuilderTools::getEditor(static::CANCELLER);
            $canceller->addStep($player, $undoArray);
        }
        if($saveRedo) {
            /** @var Canceller $canceller */
            $canceller = BuilderTools::getEditor(static::CANCELLER);
            $canceller->addRedo($player, $redoArray);
        }

        return new EditorResult($count, microtime(true)-$startTime);
    }

    /**
     * @param Player $player
     * @param UpdateLevelData $updateData
     * @param bool $saveUndo
     * @param bool $saveRedo
     *
     * @return EditorResult
     */
    public function merge(Player $player, UpdateLevelData $updateData, bool $saveUndo = true, bool $saveRedo = false): EditorResult {
        return $this->fill($player, $updateData, $saveUndo, $saveRedo, true);
    }

    /**
     * @param Level $level
     * @param int $minX
     * @param int $minZ
     * @param int $maxX
     * @param int $maxZ
     */
    private function reloadChunks(Level $level, int $minX, int $minZ, int $maxX, int $maxZ): void {
        for($x = $minX >> 4; $x <= $maxX >> 4; $x++) {
            for ($z = $minZ >> 4; $z <= $maxZ >> 4; $z++) {
                $tiles = $level->getChunkTiles($x, $z);
                $chunk = $level->getChunk($x, $z);
                $level->setChunk($x, $z, $chunk);

                foreach ($tiles as $tile) {
                    $tile->closed = false;
                    $tile->setLevel($level);
                    $level->addTile($tile);
                }

                foreach ($level->getChunkLoaders($x, $z) as $chunkLoader) {
                    if ($chunkLoader instanceof Player) {
                        if (!class_exists(LevelChunkPacket::class)) {
                            $pk = new FullChunkDataPacket();
                            $pk->chunkX = $x;
                            $pk->chunkZ = $z;
                            $pk->data = $chunk->networkSerialize();
                        } else {
                            $pk = LevelChunkPacket::withoutCache($x, $z, $chunk->getSubChunkSendCount(), $chunk->networkSerialize());
                        }
                        $chunkLoader->dataPacket($pk);
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Filler";
    }
}