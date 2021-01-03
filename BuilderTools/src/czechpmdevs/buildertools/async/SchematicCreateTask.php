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

namespace czechpmdevs\buildertools\async;

use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\editors\object\BlockMap;
use Exception;
use pocketmine\block\Air;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\AsyncTask;

/**
 * Class SchematicCreateTask
 * @package czechpmdevs\buildertools\async
 */
class SchematicCreateTask extends AsyncTask {

    /** @var string $file */
    public string $file;

    /** @var string|null $blockList */
    public ?string $blockList;

    /** @var string $center */
    public string $center;

    /** @var string $axis */
    public string $axis;

    /** @var string $materials */
    public string $materials;

    /**
     * SchematicCreateTask constructor.
     * @param string $file
     * @param BlockList $blockList
     * @param Vector3 $axis
     * @param string $materials
     */
    public function __construct(string $file, BlockList $blockList, Vector3 $axis, string $materials) {
        $this->file = $file;
        $this->blockList = serialize($blockList->setLevel(null));
        $this->axis = serialize($axis);
        $this->materials = $materials;
    }

    public function onRun() {
        try {
            /** @var Vector3 $axis */
            $axis = unserialize($this->axis);
            /** @var BlockList $blockList */
            $blockList = unserialize($this->blockList);
            $this->blockList = null;

            $materials = $this->materials;
            $blocks = "";
            $data = "";

            $this->blockList = null;

            foreach ($blockList->getAll() as $block) {
                $blocks .= chr($block->getId());
                $data .= chr($block->getDamage());
            }

            $nbt = new BigEndianNBTStream();
            $fileData = $nbt->writeCompressed(new CompoundTag
            ('Schematic', [
                new ByteArrayTag('Blocks', $blocks),
                new ByteArrayTag('Data', $data),
                new ShortTag('Height', $axis->getY()),
                new ShortTag('Length', $axis->getZ()),
                new ShortTag('Width', $axis->getX()),
                new StringTag('Materials', $materials)
            ]));

            file_put_contents($this->file, $fileData);
        }
        catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

}