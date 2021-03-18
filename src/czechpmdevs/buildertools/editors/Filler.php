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
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use InvalidArgumentException;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;
use function max;
use function microtime;
use function min;

class Filler {
    use SingletonTrait;

    public function directFill(Player $player, Vector3 $pos1, Vector3 $pos2, string $blockArgs, bool $hollow = false): EditorResult {
        $startTime = microtime(true);

        $minX = (int)min($pos1->getX(), $pos2->getX());
        $maxX = (int)max($pos1->getX(), $pos2->getX());
        $minZ = (int)min($pos1->getZ(), $pos2->getZ());
        $maxZ = (int)max($pos1->getZ(), $pos2->getZ());

        $minY = (int)max(min($pos1->getY(), $pos2->getY(), World::Y_MAX), 0);
        $maxY = (int)min(max($pos1->getY(), $pos2->getY(), 0), World::Y_MAX);

        $stringToBlockDecoder = new StringToBlockDecoder($blockArgs);

        $fillSession = new FillSession($player->getWorld(), false);
        $fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);

        if($hollow) {
            for($x = $minX; $x <= $maxX; ++$x) {
                for($z = $minZ; $z <= $maxZ; ++$z) {
                    for($y = $minY; $y <= $maxY; ++$y) {
                        if(($x != $minX && $x != $maxX) && ($y != $minY && $y != $maxY) && ($z != $minZ && $z != $maxZ)) {
                            continue;
                        }

                        $stringToBlockDecoder->nextBlock($id, $meta);
                        $fillSession->setBlockAt($x, $y, $z, $id, $meta);
                    }
                }
            }
        } else {
            for($x = $minX; $x <= $maxX; ++$x) {
                for($z = $minZ; $z <= $maxZ; ++$z) {
                    for($y = $minY; $y <= $maxY; ++$y) {
                        $stringToBlockDecoder->nextBlock($id, $meta);
                        $fillSession->setBlockAt($x, $y, $z, $id, $meta);
                    }
                }
            }
        }

        $fillSession->reloadChunks($player->getWorld());

        /** @var BlockArray $changes */
        $changes = $fillSession->getChanges();
        Canceller::getInstance()->addStep($player, $changes);

        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
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

        $iterator = new SubChunkExplorer($level);

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
//            BuilderTools::getInstance()->getLogger()->debug("$x:$y:$z");

            if($iterator->currentChunk === null) {
                BuilderTools::getInstance()->getLogger()->error("Error while filling: Chunk for $x:$y:$z is not generated.");
                continue;
            }

            if($iterator->currentSubChunk === null) {
                /** @phpstan-ignore-next-line */
                $iterator->currentSubChunk = $iterator->world->getChunk($x >> 4, $z >> 4)->getSubChunk($y >> 4); // It is checked above
            }

            if($replaceOnlyAir) {
                if($iterator->currentSubChunk->getFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f) >> 4 != 0) {
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

        $this->reloadChunks($player->getWorld(), (int)$minX, (int)$minZ, (int)$maxX, (int)$maxZ);

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

    private function reloadChunks(World $level, int $minX, int $minZ, int $maxX, int $maxZ): void {
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