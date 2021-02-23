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

namespace czechpmdevs\buildertools\schematics;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use pocketmine\math\Vector3;

/**
 * Class SchematicData
 * @package czechpmdevs\buildertools\schematics
 */
abstract class SchematicData extends BlockArray {

    public const MATERIALS_CLASSIC = "Classic";
    public const MATERIALS_BEDROCK = "Pocket";
    public const MATERIALS_ALPHA = "Alpha";

    /** @var string $file */
    private $file;

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
    protected $materialType = SchematicData::MATERIALS_BEDROCK;

    /**
     * SchematicData constructor.
     */
    public function __construct() {
        parent::__construct(true);
    }

    /**
     * @param string $targetFile
     */
    abstract public function save(string $targetFile): void;

    /**
     * @return string
     */
    public function getFile(): string {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile(string $file): void {
        $this->file = $file;
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
     * @return string
     */
    public function getMaterialType(): string {
        return $this->materialType;
    }

    /**
     * @param string $materialType
     */
    public function setMaterialType(string $materialType): void {
        $this->materialType = $materialType;
    }

    /**
     * @return Vector3
     */
    public function getAxisVector(): Vector3 {
        return new Vector3($this->getXAxis(), $this->getYAxis(), $this->getZAxis());
    }

    /**
     * @param Vector3 $vector3
     */
    public function setAxisVector(Vector3 $vector3): void {
        $this->width = $vector3->getX();
        $this->height = $vector3->getY();
        $this->length = $vector3->getZ();
    }
}