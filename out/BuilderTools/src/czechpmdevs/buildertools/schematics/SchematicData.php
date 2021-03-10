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

abstract class SchematicData extends BlockArray {

    public const MATERIALS_CLASSIC = "Classic";
    public const MATERIALS_BEDROCK = "Pocket";
    public const MATERIALS_ALPHA = "Alpha";

    /** @var string */
    private $file;

    /**
     * @var int
     *
     * Size along the x axis
     */
    protected $width;

    /**
     * @var int
     *
     * Size along the y axis
     */
    protected $height;

    /**
     * @var int
     *
     * Size along the z axis
     */
    protected $length;

    /** @var string */
    protected $materialType = SchematicData::MATERIALS_BEDROCK;

    public function __construct() {
        parent::__construct(true);
    }

    abstract public function save(string $targetFile): void;

    public function getFile(): string {
        return $this->file;
    }

    public function setFile(string $file): void {
        $this->file = $file;
    }

    public function getXAxis(): int {
        return $this->width;
    }

    public function getYAxis(): int {
        return $this->height;
    }

    public function getZAxis(): int {
        return $this->length;
    }

    public function getMaterialType(): string {
        return $this->materialType;
    }

    public function setMaterialType(string $materialType): void {
        $this->materialType = $materialType;
    }

    public function getAxisVector(): Vector3 {
        return new Vector3($this->getXAxis(), $this->getYAxis(), $this->getZAxis());
    }

    public function setAxisVector(Vector3 $vector3): void {
        $this->width = $vector3->getX();
        $this->height = $vector3->getY();
        $this->length = $vector3->getZ();
    }
}