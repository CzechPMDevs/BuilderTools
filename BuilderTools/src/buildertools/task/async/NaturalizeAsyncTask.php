<?php

declare(strict_types=1);

namespace buildertools\task\async;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class NaturalizeAsyncTask
 * @package buildertools\task\async
 */
class NaturalizeAsyncTask extends AsyncTask {

    private $resultData = [];

    /**
     * NaturalizeAsyncTask constructor.
     * @param Level $level
     */
    public function __construct(Level $level, int $x1, int $y1, int $z1, int $x2, int $y2, int $z2) {
        $this->resultData = ["level" => $level,
            "x1" => $x1, "y1" => $y1, "z1" => $z1,
            "x2" => $x2, "y2" => $y2, "z2" => $z2];
    }

    public function onRun() {
        $this->setResult($this->resultData);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {

        $result = $this->getResult();

        /** @var Level $level */
        $level = $result["level"];

        $x1 = $result["x1"]; $y1 = $result["y1"];  $z1 = $result["z1"];
        $x2 = $result["x1"]; $y2 = $result["y1"];  $z2 = $result["z1"];

        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                for($y = max($y1, $y2); $y >= min($y1, $y2); $y--) {
                    $grass = false;
                    $dirt = false;
                    $vec = new Vector3($x, $y, $z);
                    check:
                    if(!$grass && $level->getBlock($vec)->getId() !== 0) {
                        $level->setBlock($vec, Block::get(Block::GRASS), true, true);
                        $grass = true;
                        if($y > min($y1, $y2)) {
                            $vec->subtract(0, 1, 0);
                            return;
                        }
                        goto check;
                    }
                    elseif(!$grass && $level->getBlock($vec)->getId() == 0) {
                        if($y > min($y1, $y2)) {
                            $vec->subtract(0, 1, 0);
                            return;
                        }
                        goto check;
                    }
                }
            }
        }
    }
}