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
use pocketmine\nbt\tag\IntTag;
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
        try {
            $width = (int)$data->getShort("Width");
            $height = (int)$data->getShort("Height");
            $length = (int)$data->getShort("Length");

            $blockIdMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "vanilla/block_id_map.json"), true);
            var_dump($blockIdMap);

            $palette = [];
            /**
             * @var string $index
             * @var IntTag $value
             */
            foreach ($data->getCompoundTag("Palette")->getValue() as $index => $value) {
                $blockIndex = explode("[", $index)[0];
                $palette[$value->getValue()] = isset($blockIdMap[$blockIndex]) ? $blockIdMap[$blockIndex] : 0;
                var_dump($blockIndex);
            }

            $blocks = $data->getByteArray("BlockData");
            $blockList = new BlockList();

            $index = 0;
            $i = 0;

            while ($i < strlen($blocks)) {
                $value = 0;
                $varintLength = 0;

                while (true) {
                    $value |= (((int)($blocks{$i})) & 127) << ($varintLength++ * 7);
                    if($varintLength > 5) {
                        return ["error" => "VarInt is too big"];
                    }
                    if ((((int)($blocks{$i})) & 128) != 128) {
                        $i++;
                        break;
                    }
                    $i++;
                }

                $y = $index / ($width * $length);
                $z = ($index % ($width * $length)) / $width;
                $x = ($index % ($width * $length)) % $width;

                $id = $palette[$value];

                $blockList->addBlock(new Vector3($x, $y, $z), Block::get($id));
            }

            return [
                "error" => "",
                (new Fixer())->fixBlockList($blockList), // I don't know any working mcpe schematics creator
                new Vector3($width, $height, $length),
                "Pocket"
            ];
        }
        catch (\Exception $exception) {
        }

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

        BuilderTools::getInstance()->getLogger()->info(basename($file, ".schematic") . " schematic loaded!");
        BuilderTools::getSchematicsManager()->registerSchematic($file, Schematic::loadFromAsync($result));
    }
}