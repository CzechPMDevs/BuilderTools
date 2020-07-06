<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\schematics\Schematic;
use pocketmine\block\Block;
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
    public $path;

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
            if($data->offsetExists("Blocks") && $data->offsetExists("Data")) {
                $result = $this->loadMCEditFormat($data);
            }
            else {
                $result = $this->loadSpongeFormat($data);
            }

            $this->setResult($result);
        }
        catch (\Exception $exception) {
            $this->setResult(["error" => $exception->getMessage()]);
        }
    }

    /**
     * @param CompoundTag $data
     * @return array
     */
    public function loadSpongeFormat(CompoundTag $data) {
        return ["error" => "Sponge schematics still aren't supported. Try find schematics in MCEdit format."];
    }

    /**
     * @param CompoundTag $data
     * @return array
     */
    public function loadMCEditFormat(CompoundTag $data) {
        try {
            $materials = "Classic";

            $width = (int)$data->getShort("Width");
            $height = (int)$data->getShort("Height");
            $length = (int)$data->getShort("Length");

            if($data->offsetExists("Materials")) {
                $materials = $data->getString("Materials");
            }

            $blockList = new BlockList();

            if($data->offsetExists("Blocks") && $data->offsetExists("Data")) {
                $blocks = $data->getByteArray("Blocks");
                $data = $data->getByteArray("Data");

                $i = 0;
                for($y = 0; $y < $height; $y++) {
                    for ($z = 0; $z < $length; $z++) {
                        for($x = 0; $x < $width; $x++) {
                            $id = ord($blocks{$i});
                            $damage = ord($data{$i});
                            if($damage >= 16) $damage = 0; // prevents bug
                            $blockList->addBlock(new Vector3($x, $y, $z), Block::get($id, $damage));
                            $i++;
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
                $blockList = (new Fixer())->fixBlockList($blockList);
            }

            return [
                "error" => "",
                $blockList,
                new Vector3($width, $height, $length),
                $materials
            ];
        }
        catch (\Error $exception) {
            return ["error" => $exception->getMessage()];
        }
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {
        $result = $this->getResult();
        $file = $this->path;

        if(isset($result["error"]) && $result["error"] !== "") {
            BuilderTools::getInstance()->getLogger()->error("Could not load schematic $file: " . $result["error"]);
            return;
        }

        BuilderTools::getInstance()->getLogger()->info(basename($file, ".schematic") . " schematic loaded!");
        BuilderTools::getSchematicsManager()->registerSchematic($file, Schematic::loadFromAsync($result));
    }
}