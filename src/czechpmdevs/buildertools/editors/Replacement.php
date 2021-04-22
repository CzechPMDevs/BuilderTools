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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\SingletonTrait;
use function max;
use function microtime;
use function min;

class Replacement {
    use SingletonTrait;

    public function directReplace(Player $player, Vector3 $pos1, Vector3 $pos2, string $blocks, string $replace): EditorResult {
        $startTime = microtime(true);

        $mask = new StringToBlockDecoder($blocks, $player->getInventory()->getItemInHand());
        $stringToBlockDecoder = new StringToBlockDecoder($replace, $player->getInventory()->getItemInHand());

        if(!$mask->isValid()) { // Nothing to replace
            return EditorResult::success(0, microtime(true) - $startTime);
        }
        if(!$stringToBlockDecoder->isValid()) {
            return EditorResult::error("Could not read blocks from $blocks");
        }

        $minX = (int)min($pos1->getX(), $pos2->getX());
        $maxX = (int)max($pos1->getX(), $pos2->getX());
        $minZ = (int)min($pos1->getZ(), $pos2->getZ());
        $maxZ = (int)max($pos1->getZ(), $pos2->getZ());

        $minY = (int)max(min($pos1->getY(), $pos2->getY(), Level::Y_MAX), 0);
        $maxY = (int)min(max($pos1->getY(), $pos2->getY(), 0), Level::Y_MAX);

        $fillSession = new MaskedFillSession($player->getLevelNonNull(), false, true, $mask);
        $fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);

        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                for($y = $minY; $y <= $maxY; ++$y) {
                    $stringToBlockDecoder->nextBlock($id, $meta);
                    $fillSession->setBlockAt($x, $y, $z, $id, $meta);
                }
            }
        }

        $fillSession->reloadChunks($player->getLevelNonNull());
        $fillSession->close();

        /** @var BlockArray $changes */
        $changes = $fillSession->getChanges();
        $changes->save();
        Canceller::getInstance()->addStep($player, $changes);

        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true)-$startTime);
    }
}