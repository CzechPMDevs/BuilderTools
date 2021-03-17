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
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use InvalidArgumentException;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\Player;
use pocketmine\utils\SingletonTrait;
use function microtime;

class Filler {
    use SingletonTrait;

    public function prepareFill(Vector3 $pos1, Vector3 $pos2, Level $level, string $blockArgs, bool $filled = true): BlockArray {
        $stringToBlockDecoder = new StringToBlockDecoder($blockArgs);

        $updates = new BlockArray();
        $updates->setLevel($level);

        foreach (BlockGenerator::fillCuboid($pos1, $pos2, !$filled) as $vector3) {
            $stringToBlockDecoder->nextBlock($id, $meta);
            $updates->addBlock($vector3, $id, $meta);
        }

        return $updates;
    }

    public function fill(Player $player, UpdateLevelData $updateData, ?Vector3 $relativePosition = null, bool $saveUndo = true, bool $saveRedo = false, bool $replaceOnlyAir = false): EditorResult {
        if($relativePosition !== null && !$relativePosition->equals($relativePosition->ceil())) {
            throw new InvalidArgumentException("Vector3 coordinates must be integer.");
        }

        $startTime = microtime(true);
        $count = $updateData->size();

        $undoArray = $saveUndo ? (new BlockArray())->setLevel($updateData->getLevel()) : null;
        $redoArray = $saveRedo ? (new BlockArray())->setLevel($updateData->getLevel()) : null;

        $level = $updateData->getLevel();
        if($level === null) {
            throw new InvalidArgumentException("Level is not specified in update level data.");
        }

        $iterator = new SubChunkIteratorManager($level);

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
            if($relativePosition !== null) {
                $x += $relativePosition->getX();
                $y += $relativePosition->getY();
                $z += $relativePosition->getZ();
            }

            // min and max positions
            if($minX === null || $x < $minX) $minX = $x;
            if($minZ === null || $z < $minZ) $minZ = $z;
            if($maxX === null || $x > $maxX) $maxX = $x;
            if($maxZ === null || $z > $maxZ) $maxZ = $z;

            $iterator->moveTo((int)$x, (int)$y, (int)$z);

            if($iterator->currentChunk === null) {
                BuilderTools::getInstance()->getLogger()->error("Error while filling: Chunk for $x:$y:$z is not generated.");
                continue;
            }

            if($iterator->currentSubChunk === null) {
                /** @phpstan-ignore-next-line */
                $iterator->currentSubChunk = $iterator->level->getChunk($x >> 4, $z >> 4)->getSubChunk($y >> 4, true); // It is checked above
            }

            if($replaceOnlyAir) {
                if($iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) != BlockIds::AIR) {
                    continue;
                }
            }

            if($saveUndo)
                /** @var BlockArray $undoArray */
                $undoArray->addBlock(new Vector3($x, $y, $z), $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
            if($saveRedo)
                /** @var BlockArray $redoArray */
                $redoArray->addBlock(new Vector3($x, $y, $z), $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));

            $iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id, $meta);
        }

        $this->reloadChunks($player->getLevelNonNull(), (int)$minX, (int)$minZ, (int)$maxX, (int)$maxZ);

        if($saveUndo) {
            /** @var BlockArray $undoArray */
            Canceller::getInstance()->addStep($player, $undoArray);
        }
        if($saveRedo) {
            /** @var BlockArray $redoArray */
            Canceller::getInstance()->addRedo($player, $redoArray);
        }

        return EditorResult::success($count, microtime(true) - $startTime);
    }

    public function merge(Player $player, UpdateLevelData $updateData, ?Vector3 $relativePosition = null, bool $saveUndo = true, bool $saveRedo = false): EditorResult {
        return $this->fill($player, $updateData, $relativePosition, $saveUndo, $saveRedo, true);
    }

    private function reloadChunks(Level $level, int $minX, int $minZ, int $maxX, int $maxZ): void {
        for($x = $minX >> 4; $x <= $maxX >> 4; ++$x) {
            for ($z = $minZ >> 4; $z <= $maxZ >> 4; ++$z) {
                $tiles = $level->getChunkTiles($x, $z);
                $chunk = $level->getChunk($x, $z);
                $level->setChunk($x, $z, $chunk);

                foreach ($tiles as $tile) {
                    $tile->closed = false;
                    $tile->setLevel($level);
                    $level->addTile($tile);
                }

                foreach ($level->getChunkLoaders($x, $z) as $chunkLoader) {
                    if ($chunkLoader instanceof Player && $chunk !== null) {
                        $chunkLoader->dataPacket(LevelChunkPacket::withoutCache($x, $z, $chunk->getSubChunkSendCount(), $chunk->networkSerialize()));
                    }
                }
            }
        }
    }
}