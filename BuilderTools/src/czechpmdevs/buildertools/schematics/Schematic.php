<?php

/**
 * Copyright (C) 2018-2019  CzechPMDevs
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

use czechpmdevs\buildertools\async\SchematicLoadTask;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\editors\object\BlockList;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;

/**
 * Class Schematic
 * @package czechpmdevs\buildertools\schematics
 */
class Schematic {

    public const SCHEMATIC_NORMAL_TYPE = 0;
    public const SCHEMATIC_UNLOADED_TYPE = 1;

    /** @var bool $isLoaded */
    public $isLoaded = false;

    /** @var string $file */
    protected $file;

    /** @var CompoundTag $data */
    protected $data;

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

    /**
     * @var string $materials
     *
     * Classic -> MC:JAVA world format
     * Pocket -> MC:BEDROCK world format
     * Alpha -> MC:ALPHA world format - same as java
     */
    protected $materials = "Classic";


    /**
     * Schematic constructor.
     * @param string $file
     */
    public function __construct(string $file) {
        Server::getInstance()->getAsyncPool()->submitTask(new SchematicLoadTask($file));
    }

    /**
     * @return BlockList
     */
    public function getBlockList(): ?BlockList {
        return $this->blockList;
    }

    /**
     * @return CompoundTag
     */
    public function getCompoundTag(): CompoundTag {
        return $this->data;
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
        return $this->getZAxis();
    }

    /**
     * @param array $result
     */
    public function loadFromAsync(array $result) {
        if($result["error"] !== "") {
            BuilderTools::getInstance()->getLogger()->error($result["error"]);
            return;
        }
        unset($result["error"]);

        foreach ($result as $i => $v) {
            $this->{$i} = $v;
        }
        $this->isLoaded = true;
    }
}