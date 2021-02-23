<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\blockstorage;

use Generator;
use pocketmine\level\Level;

/**
 * Interface UpdateLevelData
 * @package czechpmdevs\buildertools\blockstorage
 */
interface UpdateLevelData {

    /**
     * @deprecated
     *
     * @return Generator<int, int, int, int, int>
     */
    public function read(): Generator;

    /**
     * Returns if it is possible read next blocks
     */
    public function hasNext(): bool;

    /**
     * Reads next block from the array
     */
    public function readNext(?int &$x, ?int &$y, ?int &$z, ?int &$id, ?int &$meta): void;

    /**
     * Returns how many blocks should have been
     * updated
     */
    public function size(): int;

    /**
     * Should not be null when used in filler
     */
    public function getLevel(): ?Level;
}