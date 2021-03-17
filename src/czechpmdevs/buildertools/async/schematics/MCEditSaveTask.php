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

namespace czechpmdevs\buildertools\async\schematics;

use czechpmdevs\buildertools\schematics\SchematicData;
use Exception;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\AsyncTask;
use function chr;
use function file_put_contents;

class MCEditSaveTask extends AsyncTask {

    /** @var string */
    public string $file;

    /** @var SchematicData */
    public SchematicData $schematic;

    public function __construct(SchematicData $schematic) {
        $this->schematic = $schematic;
    }

    /** @noinspection PhpUnused */
    public function onRun() {
        try {
            $blocks = "";
            $data = "";

            while ($this->schematic->hasNext()) {
                $this->schematic->readNext($x, $y, $z, $id, $meta);
                $blocks .= chr($id);
                $data .= chr($meta);
            }

            $nbt = new BigEndianNBTStream();
            $fileData = $nbt->writeCompressed(new CompoundTag('Schematic', [
                new ByteArrayTag('Blocks', $blocks),
                new ByteArrayTag('Data', $data),
                new ShortTag('Width', $this->schematic->getXAxis()),
                new ShortTag('Height', $this->schematic->getYAxis()),
                new ShortTag('Length', $this->schematic->getZAxis()),
                new StringTag('Materials', $this->schematic->getMaterialType())
            ]));

            file_put_contents($this->file, $fileData);
        }
        catch (Exception $ignore) {} // TODO - Handle errors
    }

}