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

namespace czechpmdevs\buildertools\blockstorage;

use InvalidArgumentException;
use pocketmine\level\ChunkManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Saves block with it's position as Vector3
 * Uses 17x less memory than regular array with full block int
 * Uses 62x less memory than regular array with class
 */
class BlockArray implements UpdateLevelData {
    use DuplicateBlockDetector;

    /** @var string */
    public string $buffer = "";
    /** @var int */
    public int $offset = 0;

    /** @var BlockArraySizeData|null */
    protected ?BlockArraySizeData $sizeData = null;

    /** @var ChunkManager|null */
    protected ?ChunkManager $level = null;

    public function __construct(bool $detectDuplicates = false) {
        $this->detectDuplicates = $detectDuplicates;
    }

    /**
     * Adds block to the block array
     *
     * @return $this
     */
    public function addBlock(Vector3 $vector3, int $id, int $meta): BlockArray {
        return $this->addBlockAt($vector3->getFloorX(), $vector3->getFloorY(), $vector3->getFloorZ(), $id, $meta);
    }

    /**
     * Adds block to the block array
     *
     * @return $this
     */
    public function addBlockAt(int $x, int $y, int $z, int $id, int $meta): BlockArray {
        $binHash = pack("q", Level::blockHash($x, $y, $z));
        if($this->detectingDuplicates()) {
            if($this->isDuplicate($binHash)) {
                return $this;
            }

            $this->duplicateCache .= $binHash;
        }

        $this->buffer .= chr($id);
        $this->buffer .= chr($meta);
        $this->buffer .= $binHash;

        return $this;
    }

    /**
     * Returns if it is possible read next block from the array
     */
    public function hasNext(): bool {
        return $this->offset + 10 <= strlen($this->buffer);
    }

    /**
     * Reads next block in the array
     */
    public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$id, ?int &$meta): void {
        $id = ord($this->buffer[$this->offset++]);
        $meta = ord($this->buffer[$this->offset++]);

        /** @phpstan-ignore-next-line */
        $hash = unpack("q", substr($this->buffer, $this->offset, 8))[1] ?? 0;
        Level::getBlockXYZ($hash, $x, $y, $z);
        $this->offset += 8;
    }

    /**
     * @return int $size
     */
    public function size(): int {
        return strlen($this->buffer) / 10;
    }

    /**
     * Adds Vector3 to all the blocks in BlockArray
     */
    public function addVector3(Vector3 $vector3): BlockArray {
        if(!$vector3->ceil()->equals($vector3)) {
            throw new InvalidArgumentException("Vector3 coordinates must be integer.");
        }

        $blockArray = new BlockArray();

        $len = strlen($this->buffer);
        while ($this->offset < $len) {
            $blockArray->buffer .= $this->buffer[$this->offset++];
            $blockArray->buffer .= $this->buffer[$this->offset++];

            /** @phpstan-ignore-next-line */
            $hash = unpack("q", substr($this->buffer, $this->offset, 8))[1] ?? 0;
            Level::getBlockXYZ($hash, $x, $y, $z);

            $blockArray->buffer .= pack("q", Level::blockHash($x + $vector3->getX(), $y + $vector3->getY(), $z + $vector3->getZ()));

            $this->offset += 8;
        }

        $this->offset = 0;

        return $blockArray;
    }

    /**
     * Subtracts Vector3 from all the blocks in BlockArray
     */
    public function subtractVector3(Vector3 $vector3): BlockArray {
        return $this->addVector3($vector3->multiply(-1));
    }

    /**
     * @return BlockArraySizeData is used for calculating dimensions
     */
    public function getSizeData(): BlockArraySizeData {
        if($this->sizeData === null) {
            $this->sizeData = new BlockArraySizeData($this);
        }

        return $this->sizeData;
    }

    public function setLevel(?ChunkManager $level): self {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): ?ChunkManager {
        return $this->level;
    }

    /**
     * Removes all the blocks whose were checked already
     * For cleaning duplicate cache use cancelDuplicateDetection()
     */
    public function cleanGarbage(): void {
        $this->buffer = substr($this->buffer, $this->offset);
        $this->offset = 0;
    }
}