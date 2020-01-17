<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\editors\object;

/**
 * Class BlockListMetadata
 * @package czechpmdevs\buildertools\editors\object
 */
class BlockListMetadata {

    /** @var BlockList $blockList */
    private $blockList;

    /**
     * @var int $maxX
     * @var int $maxY
     * @var int $maxZ
     */
    public $maxX, $maxY, $maxZ;

    /**
     * @var int $minX
     * @var int $minY
     * @var int $minZ
     */
    public $minX, $minY, $minZ;

    /**
     * BlockListMetadata constructor.
     * @param BlockList $blockList
     */
    public function __construct(BlockList $blockList) {
        $this->blockList = $blockList;
        $this->calculateMetadata();
    }

    private function calculateMetadata() {
        foreach ($this->blockList->getAll() as $block) {
            if(is_null($this->maxX) || $this->maxX < $block->getX()) {
                $this->maxX = $block->getX();
            }
            if(is_null($this->maxY) || $this->maxY < $block->getY()) {
                $this->maxY = $block->getY();
            }
            if(is_null($this->maxZ) || $this->maxZ < $block->getZ()) {
                $this->maxZ = $block->getZ();
            }

            if(is_null($this->minX) || $this->minX > $block->getX()) {
                $this->minX = $block->getX();
            }
            if(is_null($this->minY) || $this->minY > $block->getY()) {
                $this->minY = $block->getY();
            }
            if(is_null($this->minZ) || $this->minZ > $block->getZ()) {
                $this->minZ = $block->getZ();
            }
        }
    }

    public function recalculateMetadata() {
        $this->calculateMetadata();
    }
}