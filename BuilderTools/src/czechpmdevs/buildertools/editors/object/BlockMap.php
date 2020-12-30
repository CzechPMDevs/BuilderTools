<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\editors\object;

use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use Exception;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockMap
 * @package czechpmdevs\buildertools\editors\object
 */
class BlockMap extends BlockList {

    /** @var Block[][][] $blockMap */
    protected $blockMap = [];

    /**
     * @param Vector3 $position
     * @param Block $block
     */
    public function addBlock(Vector3 $position, Block $block): void {
        $this->blockMap[$position->getX()][$position->getY()][$position->getZ()] = $block;
    }

    /**
     * @param Vector3 $vector3
     * @return Block|null
     */
    public function getBlockAt(Vector3 $vector3): ?Block {
        if(isset($this->blockMap[$vector3->getX()])) {
            if(isset($this->blockMap[$vector3->getY()])) {
                if(isset($this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()])) {
                    return $this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()];
                }
                else return null;
            }
            else return null;
        }
        else return null;
    }

    /**
     * @param Block[] $blocks
     */
    public function setAll(array $blocks): void {
        foreach ($blocks as $block) {
            $this->blockMap[$block->getX()][$block->getY()][$block->getZ()] = $block;
        }
    }

    /**
     * @return Block[] $blocks
     */
    public function getAll(): array {
        $blocks = [];
        foreach ($this->blockMap as $x => $yzb) {
            foreach ($yzb as $y => $zb) {
                foreach ($zb as $z => $block) {
                    $block->setComponents($x, $y, $z);
                    $blocks[] = $block;
                }
            }
        }
        return $blocks;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     *
     * @return bool
     */
    public function isAirAt(int $x, int $y, int $z): bool {
        return $this->isVectorInBlockMap(new Vector3($x, $y, $z)) && $this->blockMap[$x][$y][$z]->getId() == 0;
    }


    /**
     * @param Level $level
     * @param int $x
     * @param int $y
     * @param int $z
     *
     * @return bool
     */
    public function isAirInLevel(Level $level, int $x, int $y, int $z): bool {
        return $this->isVectorInBlockMap(new Vector3($x, $y, $z)) && $this->blockMap[$x][$y][$z]->getId() == 0 && $level->getBlockIdAt($x, $y, $z) == 0;
    }

    /**
     * @param Vector3 $vector3
     *
     * @return bool
     */
    public function isVectorInBlockMap(Vector3 $vector3): bool {
        try {
            return $this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()] instanceof Block;
        }
        catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getBlockMap(): array {
        return $this->blockMap;
    }
}