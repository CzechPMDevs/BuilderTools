<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\async;

use czechpmdevs\buildertools\editors\object\BlockList;
use pocketmine\block\Block;
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

    /** @var string $blocks */
    public $blockList;

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
        $this->blockList = serialize($blockList);
        $this->axis = serialize($axis);
        $this->materials = $materials;
    }

    public function onRun() {
        try {
            /** @var BlockList $blockList */
            $blockList = unserialize($this->blockList);
            /** @var Vector3 $axis */
            $axis = unserialize($this->axis);
            /** @var string $materials */
            $materials = $this->materials;

            // y -> z -> x
            $map = [];

            /**
             * @var Block $block
             */
            foreach ($blockList->getAll() as $block) {
                $map[$block->getY()][$block->getZ()][$block->getX()] = $block;
            }

            $blocks = "";
            $data = "";

            foreach ($map as $y => $zxData) {
                foreach ($zxData as $z => $xData) {
                    foreach ($xData as $x => $blockData) {
                        $blocks .= chr($blockData->getId());
                        $data .= chr($blockData->getDamage());
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