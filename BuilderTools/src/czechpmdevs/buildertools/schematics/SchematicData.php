<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\schematics;

use czechpmdevs\buildertools\editors\object\BlockList;
use pocketmine\math\Vector3;

/**
 * Class SchematicData
 * @package czechpmdevs\buildertools\schematics
 */
abstract class SchematicData {

    public const MATERIALS_CLASSIC = "Classic";
    public const MATERIALS_BEDROCK = "Pocket";
    public const MATERIALS_ALPHA = "Alpha";

    /** @var bool $isLoaded */
    public $isLoaded = false;

    /** @var BlockList $blockList */
    protected $blockList;

    /**
     * @var int $width
     *
     * Size along the x axis
     */
    protected $width;

    /**
     * @var int $height
     *
     * Size along the y axis
     */
    protected $height;

    /**
     * @var int $length
     *
     * Size along the z axis
     */
    protected $length;

    /** @var string $materialType */
    protected $materialType;

    /**
     * SchematicData constructor.
     * @param BlockList $blocks
     * @param Vector3 $axisVector
     * @param string $materialType
     */
    public function __construct(BlockList $blocks, Vector3 $axisVector, string $materialType = SchematicData::MATERIALS_BEDROCK) {
        $this->blockList = $blocks;
        $this->width = $axisVector->getX();
        $this->height = $axisVector->getY();
        $this->length = $axisVector->getZ();
        $this->materialType = $materialType;
    }

    /**
     * @return BlockList
     */
    public function getBlockList(): ?BlockList {
        return $this->blockList;
    }

    /**
     * @return int
     */
    public function getXAxis(): int {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getYAxis(): int {
        return $this->height;
    }

    /**
     * @return int
     */
    public function getZAxis(): int {
        return $this->length;
    }

    /**
     * @return Vector3
     */
    public function getAxisVector(): Vector3 {
        return new Vector3($this->getXAxis(), $this->getYAxis(), $this->getZAxis());
    }
}