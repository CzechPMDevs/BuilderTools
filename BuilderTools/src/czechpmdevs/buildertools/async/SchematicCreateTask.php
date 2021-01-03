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

use czechpmdevs\asyncfill\storage\ThreadSafeBlockList;
use Exception;
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

    /** @var ThreadSafeBlockList $blockList */
    public ThreadSafeBlockList $blockList;
    /** @var Vector3 $axis */
    public Vector3 $axis;

    /** @var string $materials */
    public string $materials;

    /**
     * SchematicCreateTask constructor.
     *
     * @param string $file
     * @param ThreadSafeBlockList $blockList
     * @param Vector3 $axis
     * @param string $materials
     */
    public function __construct(string $file, ThreadSafeBlockList $blockList, Vector3 $axis, string $materials) {
        $this->file = $file;
        $this->blockList = $blockList;
        $this->axis = $axis;
        $this->materials = $materials;
    }

    public function onRun() {
        try {
            $blocks = "";
            $data = "";

            foreach ($this->blockList->getAll() as $block) {
                $blocks .= chr($block->getId());
                $data .= chr($block->getDamage());
            }

            $nbt = new BigEndianNBTStream();
            $fileData = $nbt->writeCompressed(new CompoundTag('Schematic', [
                new ByteArrayTag('Blocks', $blocks),
                new ByteArrayTag('Data', $data),
                new ShortTag('Width', $this->axis->getX()),
                new ShortTag('Height', $this->axis->getY()),
                new ShortTag('Length', $this->axis->getZ()),
                new StringTag('Materials', $this->materials)
            ]));

            file_put_contents($this->file, $fileData);
        }
        catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

}