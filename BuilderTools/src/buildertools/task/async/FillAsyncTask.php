<?php

/**
 * Copyright 2018 CzechPMDevs
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

namespace buildertools\task\async;

use buildertools\BuilderTools;
use buildertools\editors\Canceller;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class FillAsyncTask
 * @package buildertools\task\async
 */
class FillAsyncTask extends AsyncTask {

    /** @var string $fillData*/
    public $fillData;

    /**
     * FillAsyncTask constructor.
     * @param array $fillData
     */
    public function __construct(array $fillData) {
        $this->fillData = serialize($fillData);
    }

    public function onRun() {
        $data = unserialize($this->fillData);
        $this->setResult($data);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server) {
        $result = $this->getResult();

        $time = microtime(true);

        if($result === null) {
            $server->getLogger()->critical("§cNULL");
        }

        /** @var Position $pos1 */
        $pos1 = new Position($result["pos1"][0], $result["pos1"][1], $result["pos1"][2], $server->getLevelByName($result["pos1"][3]));

        /** @var Position $pos2 */
        $pos2 = new Position($result["pos2"][0], $result["pos2"][1], $result["pos2"][2], $server->getLevelByName($result["pos2"][3]));

        /** @var array $blocks */
        $blocks = $result["blocks"];

        /** @var Player $player */
        $player = $server->getPlayer($result["player"]);
        
        /** @var array $args */
        $args = explode(",", strval($blocks));

        if($pos1->getLevel()->getName() !== $pos2->getLevel()->getName()) {
            return;
        }
        
        $count = 0;

        $undo = [];

        for($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); $x++) {
            for($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); $y++) {
                for($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); $z++) {
                    array_push($undo, $pos1->getLevel()->getBlock(new Vector3($x, $y, $z)));
                    $pos1->getLevel()->setBlock(new Vector3($x, $y, $z), Item::fromString($args[array_rand($args, 1)])->getBlock());
                    $count++;
                }
            }
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $undo);

        $time = round(microtime(true)-$time, 4);

        $player->sendMessage(BuilderTools::getPrefix()."§aSelected area successfully filled in $time sec. using async task! ($count blocks changed!)");
        
    }
}
