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

namespace czechpmdevs\buildertools\editors\object;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use Exception;
use pocketmine\level\ChunkManager;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\utils\MainLogger;

class FillSession {

    /** @var SubChunkIteratorManager */
    private SubChunkIteratorManager $iterator;

    /** @var bool */
    private bool $calculateDimensions;
    /** @var bool */
    private bool $saveChanges;

    /** @var BlockArray|null */
    private ?BlockArray $changes = null;

    /** @var int */
    private int $minX, $maxX;
    /** @var int */
    private int $minZ, $maxZ;

    /** @var int */
    private int $blocksChanged = 0;

    public function __construct(ChunkManager $world, bool $calculateDimensions = true, bool $saveChanges = true) {
        $this->iterator = new SubChunkIteratorManager($world);

        $this->calculateDimensions = $calculateDimensions;
        $this->saveChanges = $saveChanges;

        if($this->saveChanges) {
            $this->changes = (new BlockArray())->setLevel($world);
        }
    }

    public function setDimensions(int $minX, int $maxX, int $minZ, int $maxZ) {
        $this->minX = $minX;
        $this->maxX = $maxX;
        $this->minZ = $minZ;
        $this->maxZ = $maxZ;
    }

    /**
     * @param int $y 0-255
     */
    public function setBlockAt(int $x, int $y, int $z, int $id, int $meta) {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }

        $this->saveChanges($x, $y, $z);

        $this->iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id, $meta);
        $this->blocksChanged++;
    }

    /**
     * @param int $y 0-255
     */
    public function setBlockIdAt(int $x, int $y, int $z, int $id) {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }

        $this->saveChanges($x, $y, $z);

        $this->iterator->currentSubChunk->setBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f, $id);
        $this->blocksChanged++;
    }

    public function getChanges(): ?BlockArray {
        return $this->changes;
    }

    /**
     * @return int
     */
    public function getBlocksChanged(): int {
        return $this->blocksChanged;
    }

    private function moveTo(int $x, int $y, int $z): bool {
        $this->iterator->moveTo($x, $y, $z);

        if($this->iterator->currentSubChunk === null) {
            try {
                $this->iterator->level->getChunk($x >> 4, $z >> 4)->getSubChunk($y >> 4, true);
                MainLogger::getLogger()->debug("[BuilderTools] Generated new sub chunk at $x:$y:$z!");
            } catch (Exception $exception) {
                MainLogger::getLogger()->debug("[BuilderTools] Chunk at " . ($x >> 4) . ":" . ($z >> 4) . " does not exist. Skipping the block...");
                return false;
            }
        }

        if($this->calculateDimensions) {
            if($this->minX === null || $x < $this->minX) $this->minX = $x;
            if($this->minZ === null || $z < $this->minZ) $this->minZ = $z;
            if($this->maxX === null || $x > $this->maxX) $this->maxX = $x;
            if($this->maxZ === null || $z > $this->maxZ) $this->maxZ = $z;
        }

        return true;
    }

    private function saveChanges(int $x, int $y, int $z) {
        if($this->saveChanges) {
            $id = $this->iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f);
            $meta = $this->iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f);

            $this->changes->addBlockAt($x, $y, $z, $id, $meta);
        }
    }

    public function reloadChunks(Level $level) {
        $minX = $this->minX >> 4;
        $maxX = $this->maxX >> 4;
        $minZ = $this->minZ >> 4;
        $maxZ = $this->maxZ >> 4;

        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                $level->clearChunkCache($x, $z);
                $level->getChunk($x, $z)->setChanged();

                foreach ($level->getChunkLoaders($x, $z) as $loader) {
                    $loader->onChunkChanged($level->getChunk($x, $z));
                }
            }
        }
    }
}