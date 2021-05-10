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

use czechpmdevs\buildertools\BuilderTools;
use InvalidStateException;
use pocketmine\level\ChunkManager;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use Serializable;
use function array_combine;
use function array_keys;
use function array_reverse;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_string;
use function microtime;
use function pack;
use function unpack;
use const SORT_REGULAR;

class BlockArray implements UpdateLevelData, Serializable {

    /** @var bool */
    protected bool $detectDuplicates;

    /** @var int[] */
    public array $coords = [];
    /** @var int[] */
    public array $blocks = [];

    /** @var BlockArraySizeData|null */
    protected ?BlockArraySizeData $sizeData = null;
    /** @var ChunkManager|null */
    protected ?ChunkManager $level = null;

    /** @var bool */
    protected bool $isCompressed = false;
    /** @var string */
    public string $compressedCoords;

    /** @var string */
    public string $compressedBlocks;

    /** @var int */
    public int $offset = 0;

    /**
     * Fields to avoid allocating memory every time
     * when writing or reading block from the
     * array
     */
    protected int $lastHash, $lastBlockHash;

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
        $this->lastHash = Level::blockHash($x, $y, $z);

        if($this->detectDuplicates && in_array($this->lastHash, $this->coords)) {
            return $this;
        }

        $this->coords[] = $this->lastHash;
        $this->blocks[] = $id << 4 | $meta;

        return $this;
    }

    /**
     * Returns if it is possible read next block from the array
     */
    public function hasNext(): bool {
        return $this->offset < count($this->blocks);
    }

    /**
     * Reads next block in the array
     */
    public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$id, ?int &$meta): void {
        $this->lastHash = $this->coords[$this->offset];
        $this->lastBlockHash = $this->blocks[$this->offset++];

        Level::getBlockXYZ($this->lastHash, $x, $y, $z);
        $id = $this->lastBlockHash >> 4; $meta = $this->lastBlockHash & 0xf;
    }

    /**
     * Adds Vector3 to all the blocks in BlockArray
     */
    public function addVector3(Vector3 $vector3): BlockArray {
        $floorX = $vector3->getFloorX();
        $floorY = $vector3->getFloorY();
        $floorZ = $vector3->getFloorZ();

        $blockArray = new BlockArray();
        $blockArray->blocks = $this->blocks;

        foreach ($this->coords as $hash) {
            Level::getBlockXYZ($hash, $x, $y, $z);
            $blockArray->coords[] = Level::blockHash(($floorX + $x), ($floorY + $y), ($floorZ + $z));
        }

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
     * @return int[]
     */
    public function getBlockArray(): array {
        return $this->blocks;
    }

    /**
     * @return int[]
     */
    public function getCoordsArray(): array {
        return $this->coords;
    }

    public function removeDuplicates(): void {
        if(!(BuilderTools::getConfiguration()["remove-duplicate-blocks"] ?? true)) {
            return;
        }

        // TODO - Optimize this
        $blocks = array_combine(array_reverse($this->coords, true), array_reverse($this->blocks, true));
        if($blocks === false) {
            return;
        }

        $this->coords = array_keys($blocks);
        $this->blocks = array_values($blocks);
    }

    public function save(): void {
        if(BuilderTools::getConfiguration()["clipboard-compression"] ?? false) {
            $this->compress();
            $this->isCompressed = true;
        }
    }

    public function load(): void {
        if(BuilderTools::getConfiguration()["clipboard-compression"] ?? false) {
            $this->decompress();
            $this->isCompressed = false;
        }
    }

    public function compress(bool $cleanDecompressed = true): void {
        /** @phpstan-var string|false $coords */
        $coords = pack("q*", ...$this->coords);
        /** @phpstan-var string|false $blocks */
        $blocks = pack("N*", ...$this->blocks);

        if($coords === false || $blocks === false) {
            throw new InvalidStateException("Error whilst compressing");
        }

        $this->compressedCoords = $coords;
        $this->compressedBlocks = $blocks;

        if($cleanDecompressed) {
            $this->coords = [];
            $this->blocks = [];
        }
    }

    public function decompress(bool $cleanCompressed = true): void {
        /** @phpstan-var int[]|false $coords */
        $coords = unpack("q*", $this->compressedCoords);
        /** @phpstan-var int[]|false $coords */
        $blocks = unpack("N*", $this->compressedBlocks);

        if($coords === false || $blocks === false) {
            throw new InvalidStateException("Error whilst decompressing");
        }

        $this->coords = array_values($coords);
        $this->blocks = array_values($blocks);

        if($cleanCompressed) {
            unset($this->compressedCoords);
            unset($this->compressedBlocks);
        }
    }

    public function isCompressed(): bool {
        return $this->isCompressed;
    }

    /**
     * Removes all the blocks whose were checked already
     */
    public function cleanGarbage(): void {
        $this->coords = array_slice($this->coords, $this->offset);
        $this->blocks = array_slice($this->blocks, $this->offset);

        $this->offset = 0;
    }

    public function serialize(): ?string {
        $this->compress();

        $nbt = new CompoundTag("BlockArray");
        $nbt->setByteArray("Coords", $this->compressedCoords);
        $nbt->setByteArray("Blocks", $this->compressedBlocks);
        $nbt->setByte("DuplicateDetection", $this->detectDuplicates ? 1 : 0);

        $stream = new BigEndianNBTStream();
        $buffer = $stream->writeCompressed($nbt);

        if($buffer === false) {
            return null;
        }

        return $buffer;
    }

    public function unserialize($data): void {
        if(!is_string($data)) {
            return;
        }

        /** @var CompoundTag $nbt */
        $nbt = (new BigEndianNBTStream())->readCompressed($data);
        if(!$nbt->hasTag("Coords", ByteArrayTag::class) || !$nbt->hasTag("Blocks", ByteArrayTag::class) || !$nbt->hasTag("DuplicateDetection", ByteTag::class)) {
            return;
        }

        $this->compressedCoords = $nbt->getByteArray("Coords");
        $this->compressedBlocks = $nbt->getByteArray("Blocks");
        $this->detectDuplicates = $nbt->getByte("DuplicateDetection") == 1;

        $this->decompress();
    }
}