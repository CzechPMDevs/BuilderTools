<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\async;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\editors\object\BlockList;
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
            $result = ["error" => ""];
            $materials = "Classic";
            $nbt = new BigEndianNBTStream();

            /** @var CompoundTag $data */
            $data = $nbt->readCompressed(file_get_contents($this->path));
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

            $result[] = $blockList;
            $result[] = new Vector3($width, $height, $length);
            $result[] = $materials;

            unset($blockList, $materials, $data, $width, $height, $length);

            $this->setResult($result);
        }
        catch (\Error $exception) {
            $this->setResult(["error" => $exception->getMessage()]);
        }
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {
        $result = $this->getResult();
        $file = $this->path;

        BuilderTools::getInstance()->getLogger()->info(basename($file, ".schematic") . " schematic loaded!");
        BuilderTools::getSchematicsManager()->registerSchematic($file, Schematic::loadFromAsync($result));
    }
}