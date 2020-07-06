<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\editors\object;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Class BlockMap
 * @package czechpmdevs\buildertools\editors\object
 */
class BlockMap {

    /** @var Block[][][] $blockMap */
    protected $blockMap = [];

    /**
     * @return array
     */
    public function getBlockMap(): array {
        return $this->blockMap;
    }

    /**
     * @param Vector3 $vector3
     * @return Block|null
     */
    public function getBlockAt(Vector3 $vector3) {
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
    public function setAll(array $blocks) {
        foreach ($blocks as $block) {
            $this->blockMap[$block->getX()][$block->getY()][$block->getZ()] = $block;
        }
    }

    /**
     * @return Block[] $blocks
     */
    public function getAll() {
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
            $block = $this->blockMap[$vector3->getX()][$vector3->getY()][$vector3->getZ()];
            if($block instanceof Block) {
                return true;
            }
            return false;
        }
        catch (\Exception $exception) {
            return false;
        }
    }
}