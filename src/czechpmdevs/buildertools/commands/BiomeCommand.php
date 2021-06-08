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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\Math;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function array_key_exists;
use function array_keys;
use function implode;
use function is_numeric;
use function json_decode;
use function microtime;

class BiomeCommand extends BuilderToolsCommand {

    // Script to generate that https://gist.github.com/VixikHD/241fdd02dba69f62ec91c571a305c8f8
    public const BIOME_DATA = '{"ocean":0,"plains":1,"desert":2,"mountains":3,"forest":4,"swamp":5,"river":7,"hell":8,"ice_plains":12,"mushroom_fields":14,"jungle":21,"dark_forest":29,"savanna":35,"badlands":37}';

    /** @var int[] */
    private array $biomeData;

    public function __construct() {
        parent::__construct("/biome", "Updates biome for the selection");
        $this->biomeData = json_decode(BiomeCommand::BIOME_DATA, true);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cUsage: §7//biome <biome OR list>");
            return;
        }
        if($args[0] == "list") {
            $sender->sendMessage(BuilderTools::getPrefix() . "§aAvailable biomes: " . implode(", ", array_keys($this->biomeData)));
            return;
        }
        if(!$this->readPositions($sender, $firstPos, $secondPos)) {
            return;
        }

        /** @var int|null $id */
        $id = null;
        if(is_numeric($args[0]) && (int)$id <= 255 && (int)$id >= 0) {
            $id = (int)$args[0];
        }
        if(array_key_exists($args[0], $this->biomeData)) {
            $id = $this->biomeData[$args[0]];
        }

        if($id === null) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cBiome id $args[0] was not found.");
            return;
        }

        $startTime = microtime(true);

        Math::calculateMinAndMaxValues($firstPos, $secondPos, false, $minX, $maxX, $_, $_, $minZ, $maxZ);

        $fillSession = new FillSession($sender->getWorld(), false, false);
        $fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                $fillSession->setBiomeAt($x, $z, $id);
            }
        }

        $result = EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);

        $fillSession->reloadChunks($sender->getWorld());
        $sender->sendMessage(BuilderTools::getPrefix() . "§aBiomes updated, {$result->getBlocksChanged()} blocks affected in {$result->getProcessTime()}");
    }
}