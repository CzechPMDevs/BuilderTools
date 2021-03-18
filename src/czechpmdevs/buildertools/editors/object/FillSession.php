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
use pocketmine\utils\MainLogger;
use pocketmine\world\ChunkManager;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class FillSession {

    /** @var SubChunkExplorer */
    protected SubChunkExplorer $iterator;

    /** @var bool */
    protected bool $calculateDimensions;
    /** @var bool */
    protected bool $saveChanges;

    /** @var BlockArray|null */
    protected ?BlockArray $changes = null;

    /** @var int */
    protected int $minX, $maxX;
    /** @var int */
    protected int $minZ, $maxZ;

    /** @var int */
    protected int $blocksChanged = 0;
    
    /** 
     * Field to avoid re-allocating memory
     * 
     * @var int 
     */
    protected int $lastHash;

    public function __construct(ChunkManager $world, bool $calculateDimensions = true, bool $saveChanges = true) {
        $this->iterator = new SubChunkExplorer($world);

        $this->calculateDimensions = $calculateDimensions;
        $this->saveChanges = $saveChanges;

        if($this->saveChanges) {
            $this->changes = (new BlockArray())->setLevel($world);
        }
    }

    public function setDimensions(int $minX, int $maxX, int $minZ, int $maxZ): void {
        $this->minX = $minX;
        $this->maxX = $maxX;
        $this->minZ = $minZ;
        $this->maxZ = $maxZ;
    }

    /**
     * @param int $y 0-255
     */
    public function setBlockAt(int $x, int $y, int $z, int $id, int $meta): void {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }

        $this->saveChanges($x, $y, $z);

        /** @phpstan-ignore-next-line */
        $this->iterator->currentSubChunk->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id << 4 | $meta);
        $this->blocksChanged++;
    }

    /**
     * @param int $y 0-255
     */
    public function getBlockAt(int $x, int $y, int $z, ?int &$id, ?int &$meta): void {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }
        
        $this->lastHash = $this->iterator->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);

        $id = $this->lastHash >> 4;
        $meta = $this->lastHash & 0xf;
    }

    public function getChanges(): ?BlockArray {
        return $this->changes;
    }

    public function getBlocksChanged(): int {
        return $this->blocksChanged;
    }

    public function reloadChunks(World $world): void {
        $minX = $this->minX >> 4;
        $maxX = $this->maxX >> 4;
        $minZ = $this->minZ >> 4;
        $maxZ = $this->maxZ >> 4;

        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                $chunk = $world->getChunk($x, $z);
                if($chunk === null) {
                    continue;
                }

                foreach ($world->getChunkListeners($x, $z) as $listener) {
                    $listener->onChunkChanged($x, $z, $chunk);
                }
            }
        }
    }

    protected function moveTo(int $x, int $y, int $z): bool {
        $this->iterator->moveTo($x, $y, $z);

        if($this->iterator->currentSubChunk === null) {
//            MainLogger::getLogger()->debug("[BuilderTools] Chunk at " . ($x >> 4) . ":" . ($z >> 4) . " does not exist. Skipping the block...");
            return false;
        }

        if($this->calculateDimensions) {
            if(!isset($this->minX) || $x < $this->minX) $this->minX = $x;
            if(!isset($this->minZ) || $z < $this->minZ) $this->minZ = $z;
            if(!isset($this->maxX) || $x > $this->maxX) $this->maxX = $x;
            if(!isset($this->maxZ) || $z > $this->maxZ) $this->maxZ = $z;
        }

        return true;
    }

    protected function saveChanges(int $x, int $y, int $z): void {
        if($this->saveChanges) {
            $this->lastHash = $this->iterator->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);

            $id = $this->lastHash >> 4;
            $meta = $this->lastHash & 0xf;

            $this->changes->addBlockAt($x, $y, $z, $id, $meta);
        }
    }
}