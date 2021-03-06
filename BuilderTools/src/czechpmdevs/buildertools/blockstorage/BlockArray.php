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

use Generator;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Saves block with it's position as vector3
 * Uses 17x less memory than regular array with full block int
 * Uses 62x less memory than regular array with class
 *
 * Class BlockArray
 * @package czechpmdevs\buildertools\blockstorage
 */
class BlockArray implements UpdateLevelData {
    use DuplicateBlockDetector;

    /** @var string */
    public string $buffer = "";
    /** @var int */
    public int $offset = 0;

    /** @var BlockArraySizeData|null */
    protected ?BlockArraySizeData $sizeData = null;

    /** @var Level|null */
    protected ?Level $level = null;

    public function __construct(bool $detectDuplicates = false) {
        $this->detectDuplicates = $detectDuplicates;
    }

    public function addBlock(Vector3 $vector3, int $id, int $meta): self {
        $binHash = pack("q", Level::blockHash($vector3->getX(), $vector3->getY(), $vector3->getZ()));
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
     *
     * @return $this for chaining
     */
    public function addVector3(Vector3 $vector3): BlockArray {
        $vector3 = $vector3->ceil();
        $blockArray = new BlockArray();

        $len = strlen($this->buffer);
        while ($this->offset < $len) {
            $blockArray->buffer .= $this->buffer[$this->offset++];
            $blockArray->buffer .= $this->buffer[$this->offset++];

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
     *
     * @return $this for chaining
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

    public function setLevel(?Level $level): self {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): ?Level {
        return $this->level;
    }

    /**
     * Removes all the blocks whose were checked already
     * For cleaning duplicate cache use cancelDuplicateDetection()
     */
    public function cleanGarbage() {
        $this->buffer = substr($this->buffer, $this->offset);
        $this->offset = 0;
    }
}

trait DuplicateBlockDetector {

    /** @var bool */
    protected bool $detectDuplicates = false;
    /** @var string */
    protected string $duplicateCache = "";

    /**
     * Check if vector is already used in the array
     */
    protected function isDuplicate(string $binHash): bool {
        for($i = 0; ($j = (strpos($this->duplicateCache, $binHash, $i))) !== false; $i++) {
            if($j % 8 == 0) {
                return true;
            }
        }

        return false;
    }

    public function detectingDuplicates(): bool {
        return $this->detectDuplicates;
    }

    public function cancelDuplicateDetection(): void {
        $this->detectDuplicates = false;
        $this->duplicateCache = "";
    }
}