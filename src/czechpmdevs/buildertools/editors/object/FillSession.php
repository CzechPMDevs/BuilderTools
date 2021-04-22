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
use czechpmdevs\buildertools\BuilderTools;
use Error;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Solid;
use pocketmine\level\ChunkManager;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use ReflectionClass;

class FillSession {

    /** @var SubChunkIteratorManager */
    protected SubChunkIteratorManager $iterator;

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

    /** @var bool */
    protected bool $error = false;

    public function __construct(ChunkManager $world, bool $calculateDimensions = true, bool $saveChanges = true) {
        $this->iterator = new SubChunkIteratorManager($world);

        $this->calculateDimensions = $calculateDimensions;
        $this->saveChanges = $saveChanges;

        if($this->saveChanges) {
            $this->changes = (new BlockArray())->setLevel($world);
        }
    }

    /**
     * Requests block coordinates (not chunk ones)
     */
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
        $this->iterator->currentSubChunk->setBlock($x & 0xf, $y & 0xf, $z & 0xf, $id, $meta);
        $this->blocksChanged++;
    }

    /**
     * @param int $y 0-255
     */
    public function setBlockIdAt(int $x, int $y, int $z, int $id): void {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }

        $this->saveChanges($x, $y, $z);

        /** @phpstan-ignore-next-line */
        $this->iterator->currentSubChunk->setBlockId($x & 0xf, $y & 0xf, $z & 0xf, $id);
        $this->blocksChanged++;
    }

    /**
     * @param int $y 0-255
     */
    public function getBlockAt(int $x, int $y, int $z, ?int &$id, ?int &$meta): void {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $id = $this->iterator->currentSubChunk->getBlockId($x & 0xf, $y & 0xf, $z & 0xf);
        /** @phpstan-ignore-next-line */
        $meta = $this->iterator->currentSubChunk->getBlockData($x & 0xf, $y & 0xf, $z & 0xf);
    }

    /**
     * @param int $y 0-255
     */
    public function getBlockIdAt(int $x, int $y, int $z, ?int &$id): void {
        if(!$this->moveTo($x, $y, $z)) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $id = $this->iterator->currentSubChunk->getBlockId($x & 0xf, $y & 0xf, $z & 0xf);
    }

    public function setBiomeAt(int $x, int $z, int $id): void {
        if(!$this->iterator->moveTo($x, 0, $z)) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $this->iterator->currentChunk->setBiomeId($x & 0xf, $z & 0xf, $id);
        $this->blocksChanged++;
    }

    public function getHighestBlockAt(int $x, int $z, ?int &$y = null): bool {
        for($y = 255; $y >= 0; --$y) {
            $this->iterator->moveTo($x, $y, $z);

            /** @phpstan-ignore-next-line */
            $id = $this->iterator->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);
            if($id >> 4 != 0) {
                if(BlockFactory::get($id >> 4, $id & 0xf) instanceof Solid) {
                    $y++;
                    return true;
                }

                return false;
            }
        }
        return false;
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

    public function loadChunks(Level $level): void {
        $minX = $this->minX >> 4;
        $maxX = $this->maxX >> 4;
        $minZ = $this->minZ >> 4;
        $maxZ = $this->maxZ >> 4;

        for($x = $minX; $x <= $maxX; $x++) {
            for($z = $minZ; $z <= $maxZ; $z++) {
                $level->loadChunk($x, $z);
            }
        }
    }

    public function reloadChunks(Level $level): void {
        if($this->error) {
            BuilderTools::getInstance()->getLogger()->notice("Some chunks were not found");
        }

        $minX = $this->minX >> 4;
        $maxX = $this->maxX >> 4;
        $minZ = $this->minZ >> 4;
        $maxZ = $this->maxZ >> 4;

        // PocketMine unfortunately does not have method for clearing block cache per chunk.
        // I am planning to make pull request for that, however, this hack should be kept
        // for backwards compatibility.
        $blockCacheProperty = (new ReflectionClass(Level::class))->getProperty("blockCache");
        $blockCacheProperty->setAccessible(true);

        /** @var Block[][] $blockCache */
        $blockCache = $blockCacheProperty->getValue($level);
        $clearBlockCache = function (int $chunkX, int $chunkZ) use (&$blockCache): void {
            unset($blockCache[Level::chunkHash($chunkX, $chunkZ)]);
        };

        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                $level->clearChunkCache($x, $z);
                $clearBlockCache($x, $z);

                $chunk = $level->getChunk($x, $z);
                if($chunk === null) {
                    continue;
                }

                $chunk->setChanged();
                foreach ($level->getChunkLoaders($x, $z) as $loader) {
                    $loader->onChunkChanged($chunk);
                }
            }
        }

        $blockCacheProperty->setValue($level, $blockCache);
    }

    protected function moveTo(int $x, int $y, int $z): bool {
        $this->iterator->moveTo($x, $y, $z);

        if($this->iterator->currentSubChunk === null) {
            try {
                /** @phpstan-ignore-next-line */
                $this->iterator->level->getChunk($x >> 4, $z >> 4)->getSubChunk($y >> 4, true);
            } catch (Error $exception) { // For the case if chunk is null
                $this->error = true;
                return false;
            }
        }

        if($this->calculateDimensions) {
            if((!isset($this->minX)) || $x < $this->minX) $this->minX = $x;
            if((!isset($this->minZ)) || $z < $this->minZ) $this->minZ = $z;
            if((!isset($this->maxX)) || $x > $this->maxX) $this->maxX = $x;
            if((!isset($this->maxZ)) || $z > $this->maxZ) $this->maxZ = $z;
        }

        return true;
    }

    protected function saveChanges(int $x, int $y, int $z): void {
        if($this->saveChanges) {
            /** @phpstan-ignore-next-line */
            $id = $this->iterator->currentSubChunk->getBlockId($x & 0xf, $y & 0xf, $z & 0xf);
            /** @phpstan-ignore-next-line */
            $meta = $this->iterator->currentSubChunk->getBlockData($x & 0xf, $y & 0xf, $z & 0xf);
            /** @phpstan-ignore-next-line */
            $this->changes->addBlockAt($x, $y, $z, $id, $meta);
        }
    }

    public function close(): void {
        $this->iterator->invalidate();
    }
}