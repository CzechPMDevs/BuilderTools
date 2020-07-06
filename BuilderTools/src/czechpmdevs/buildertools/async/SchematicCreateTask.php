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


use czechpmdevs\buildertools\editors\blockstorage\BlockList;
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

    public $file;

    /** @var string $blockList */
    public $blockList;

    /** @var string $center */
    public $center;

    /** @var string $axis */
    public $axis;

    /** @var string $materials */
    public $materials;

    /**
     * SchematicCreateTask constructor.
     * @param string $file
     * @param BlockList $blockList
     * @param Vector3 $axis
     * @param string $materials
     */
    public function __construct(string $file, BlockList $blockList, Vector3 $axis, string $materials) {
        $this->file = $file;
        $this->blockList = serialize($this->buildBlockMap($blockList));
        $this->axis = serialize($axis);
        $this->materials = $materials;
    }

    /**
     * @param BlockList $blockList
     * @return array
     */
    public function buildBlockMap(BlockList $blockList): array {
        $map = [];

        foreach ($blockList->getAll() as $block) {
            $map[$block->getX()][$block->getY()][$block->getZ()] = [$block->getId(), $block->getDamage()];
        }

        return $map;
    }

    public function onRun() {
        try {
            /** @var array $map */
            $map = unserialize($this->blockList);
            /** @var Vector3 $axis */
            $axis = unserialize($this->axis);
            /** @var string $materials */
            $materials = $this->materials;

            $blocks = "";
            $data = "";

            $minX = null;
            $minY = null;
            $minZ = null;

            foreach ($map as $x => $yz) {
                $minX = $minX === null || $minX > $x ? $x : $minX;
                foreach ($yz as $y => $zb) {
                    $minY = $minY === null || $minY > $y ? $y : $minY;
                    foreach ($zb as $z => $b) {
                        $minZ = $minZ === null || $minZ > $z ? $z : $minZ;
                    }
                }
            }


            for ($y = 0; $y < $axis->getY(); $y++) {
                for ($z = 0; $z < $axis->getZ(); $z++) {
                    for($x = 0; $x < $axis->getX(); $x++) {
                        $block = [0, 0];
                        if(isset($map[$x + $minX][$y + $minY][$z + $minZ])) {
                            $block = $map[$x + $minX][$y + $minY][$z + $minZ];
                        }

                        $blocks .= chr($block[0]);
                        $data .= chr($block[1]);
                    }
                }
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
        catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

}