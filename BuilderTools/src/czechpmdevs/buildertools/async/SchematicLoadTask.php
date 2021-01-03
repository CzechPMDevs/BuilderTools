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

use czechpmdevs\buildertools\blockstorage\async\ThreadSafeBlock;
use czechpmdevs\buildertools\blockstorage\async\ThreadSafeBlockList;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\schematics\Schematic;
use Error;
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
class SchematicLoadTask extends AsyncTask {

    /** @var string $path */
    public string $path;

    /** @var ThreadSafeBlockList $blockList */
    public ThreadSafeBlockList $blockList;
    /** @var Vector3 $axisVector */
    public Vector3 $axisVector;
    /** @var string $materials */
    public string $materials;

    public string $error = "";

    /**
     * SchematicLoadTask constructor.
     * @param string $path
     */
    public function __construct(string $path) {
        $this->path = $path;
        $this->blockList = new ThreadSafeBlockList();
    }

    public function onRun() {
        try {
            /** @var CompoundTag $data */
            $data = (new BigEndianNBTStream())->readCompressed(file_get_contents($this->path));
            if($data->offsetExists("Blocks") && $data->offsetExists("Data")) {
                $this->loadMCEditFormat($data);
            } else {
                $this->loadSpongeFormat($data);
            }
        }
        catch (Exception $exception) {
            $this->error = "{$exception->getMessage()} in {$exception->getFile()} at line {$exception->getLine()}";
        }
    }

    /**
     * @param CompoundTag $data
     */
    public function loadSpongeFormat(CompoundTag $data): void {
        $this->error = "Sponge schematics still aren't supported. Try find schematics in MCEdit format.";
    }

    /**
     * @param CompoundTag $data
     */
    public function loadMCEditFormat(CompoundTag $data): void {
        try {
            $materials = "Classic";

            $width = (int)$data->getShort("Width");
            $height = (int)$data->getShort("Height");
            $length = (int)$data->getShort("Length");

            if($data->offsetExists("Materials")) {
                $materials = $data->getString("Materials");
            }

            if($data->offsetExists("Blocks") && $data->offsetExists("Data")) {
                $blocks = $data->getByteArray("Blocks");
                $data = $data->getByteArray("Data");

                $i = 0;
                for($y = 0; $y < $height; $y++) {
                    for ($z = 0; $z < $length; $z++) {
                        for($x = 0; $x < $width; $x++) {
                            $this->blockList->addBlock(new Vector3($x, $y, $z), new ThreadSafeBlock(ord($blocks[$i]), ord($data[$i++]) % 16));
                        }
                    }
                }
            }

            // WORLDEDIT BY SK89Q and Sponge schematics
            else {
                $result["error"] = "Could not load schematic {$this->path}: BuilderTools supports only MCEdit schematic format.";
            }

            if($materials == "Classic" || $materials == "Alpha") {
                $materials = "Pocket";
                (new Fixer())->fixThreadSafeBlockList($this->blockList);
            }

            $this->axisVector = new Vector3($width, $height, $length);
            $this->materials = $materials;
        }
        catch (Error $exception) {
            $this->error = "{$exception->getMessage()} in {$exception->getFile()} at line {$exception->getLine()}";
        }
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {
        $schematic = Schematic::loadFromAsync($this);
        if(!$schematic instanceof Schematic) {
            return;
        }

        BuilderTools::getSchematicsManager()->registerSchematic($this->path, $schematic);
    }
}