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

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\schematics\format\MCEditSchematics;
use czechpmdevs\buildertools\schematics\SchematicData;
use Exception;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class SchematicLoadingTask
 * @package czechpmdevs\buildertools\async
 */
class MCEditLoadTask extends AsyncTask {

    /** @var string $path */
    public $path;

    /** @var MCEditSchematics $schematics */
    public $schematics;

    /** @var string $error */
    public $error = "";

    /**
     * SchematicLoadTask constructor.
     * @param string $path
     */
    public function __construct(string $path) {
        $this->path = $path;
    }

    public function onRun() {
        try {
            /** @var CompoundTag $data */
            $data = (new BigEndianNBTStream())->readCompressed(file_get_contents($this->path));

            $materials = SchematicData::MATERIALS_CLASSIC;

            $width = (int)$data->getShort("Width");
            $height = (int)$data->getShort("Height");
            $length = (int)$data->getShort("Length");

            if($data->offsetExists("Materials")) {
                $materials = $data->getString("Materials");
            }

            $fixBlocks = $materials == SchematicData::MATERIALS_CLASSIC || $materials == SchematicData::MATERIALS_ALPHA;
            $fixer = new Fixer();

            $blocks = $data->getByteArray("Blocks");
            $data = $data->getByteArray("Data");

            $schematics = new MCEditSchematics();

            $i = 0;
            for($y = 0; $y < $height; $y++) {
                for ($z = 0; $z < $length; $z++) {
                    for($x = 0; $x < $width; $x++) {
                        $id = ord($blocks[$i]);
                        $meta = ord($data[$i++]);

                        if($fixBlocks) {
                            $fixer->fixBlock($id, $meta);
                        }

                        $schematics->addBlock(new Vector3($x, $y, $z), $id, $meta);
                    }
                }
            }

            $materials = SchematicData::MATERIALS_BEDROCK;

            $schematics->setAxisVector(new Vector3($width, $height, $length));
            $schematics->setMaterialType($materials);
            $schematics->setFile($this->path);

            $this->schematics = $schematics;
        }
        catch (Exception $exception) {
            $this->error = "{$exception->getMessage()} in {$exception->getFile()} at line {$exception->getLine()}";
        }
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {
        var_dump($this->schematics->size());
        BuilderTools::getSchematicsManager()->registerSchematic($this->path, $this->schematics);
    }
}