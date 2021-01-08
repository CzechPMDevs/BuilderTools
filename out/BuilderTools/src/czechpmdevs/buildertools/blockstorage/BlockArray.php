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
class BlockArray {

    /** @var string $buffer */
    public $buffer = "";
    /** @var int $offset */
    public $offset = 0;

    /** @var bool $detectDuplicates */
    protected $detectDuplicates = false;
    /** @var string $duplicateCache */
    protected $duplicateCache = "";

    /** @var BlockArraySizeData|null $sizeData */
    protected $sizeData = null;

    /**
     * BlockArray constructor.
     * @param bool $detectDuplicates
     */
    public function __construct(bool $detectDuplicates = false) {
        $this->detectDuplicates = $detectDuplicates;
    }

    /**
     * @param Vector3 $vector3
     * @param int $id
     * @param int $meta
     *
     * @return $this
     */
    public function addBlock(Vector3 $vector3, int $id, int $meta) {
        $binHash = pack("q", Level::blockHash($vector3->getX(), $vector3->getY(), $vector3->getZ()));
        if($this->detectDuplicates) {
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
     * Check if vector is already used
     *
     * @param string $binHash
     * @return bool
     */
    protected function isDuplicate(string $binHash): bool {
        for($i = 0; ($j = (strpos($this->duplicateCache, $binHash, $i))) !== false; $i++) {
            if($j % 8 == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Vector3|null $vector3
     * @param int|null $id
     * @param int|null $meta
     */
    public function nextBlock(?Vector3 &$vector3, ?int &$id, ?int &$meta): void {
        $id = ord($this->buffer[$this->offset++]);
        $meta = ord($this->buffer[$this->offset++]);

        $hash = unpack("q", substr($this->buffer, $this->offset, 8))[1] ?? 0;
        Level::getBlockXYZ($hash, $x, $y, $z);

        $vector3 = new Vector3($x, $y, $z);
        $this->offset += 8;
    }

    /**
     * Returns Generator<Vector3, id, meta>
     * cleanGarbage removes blocks from BlockArray
     * after yield
     *
     * @param bool $cleanGarbage
     * @return Generator<Vector3, int, int>
     */
    public function read(bool $cleanGarbage = true): Generator {
        while ($this->offset < strlen($this->buffer)) {
            $this->nextBlock($vector, $id, $meta);
            yield [$vector->getX(), $vector->getY(), $vector->getZ(), $id, $meta];

            if($cleanGarbage) {
                $this->cleanGarbage();
            }
        }

        if(!$cleanGarbage) {
            $this->offset = 0;
        }
    }

    /**
     * @return int $size
     */
    public function size(): int {
        return strlen($this->buffer) / 10;
    }

    /**
     * @param Vector3 $vector3
     *
     * @return BlockArray
     */
    public function addVector3(Vector3 $vector3): BlockArray {
        $blockArray = new BlockArray();

        $len = strlen($this->buffer);
        while ($this->offset < $len) {
            $blockArray->buffer .= $this->buffer[$this->offset++];
            $blockArray->buffer .= $this->buffer[$this->offset++];

            $hash = unpack("q", substr($this->buffer, $this->offset, 8))[1] ?? 0;
            Level::getBlockXYZ($hash, $x, $y, $z);

            $blockArray->buffer .= pack("q", Level::blockHash($x + $vector3->getX(), $y + $vector3->getY(), $z + $vector3->getZ()));
        }

        $this->offset = 0;

        return $this;
    }

    /**
     * @param Vector3 $vector3
     *
     * @return BlockArray
     */
    public function subtractVector3(Vector3 $vector3): BlockArray {
        return $this->addVector3($vector3->multiply(-1));
    }

    /**
     * @return BlockArraySizeData|null
     */
    public function getSizeData(): ?BlockArraySizeData {
        if($this->sizeData === null) {
            $this->sizeData = new BlockArraySizeData($this);
        }

        return $this->sizeData;
    }

    /**
     * Removes all the blocks whose were checked already
     * For cleaning duplicate cache use cancelDuplicateDetection()
     */
    public function cleanGarbage() {
        $this->buffer = substr($this->buffer, $this->offset);
        $this->offset = 0;
    }

    /**
     * @return bool
     */
    public function detectingDuplicates(): bool {
        return $this->detectDuplicates;
    }

    /**
     * Cancels duplicate vector detection and removes cache
     */
    public function cancelDuplicateDetection(): void {
        $this->detectDuplicates = false;
        $this->duplicateCache = "";
    }
}