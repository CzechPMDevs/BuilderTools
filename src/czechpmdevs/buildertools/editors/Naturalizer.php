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
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\Math;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\SingletonTrait;
use function microtime;

class Naturalizer {
    use SingletonTrait;

    public function naturalize(Vector3 $pos1, Vector3 $pos2, Player $player): EditorResult {
        $startTime = microtime(true);

        Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

        $fillSession = new FillSession($player->getLevelNonNull(), false);
        $fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);

        for($x = $minX; $x <= $maxX; ++$x) {
            for($z = $minZ; $z <= $maxZ; ++$z) {
                $state = 0;
                for($y = 255; $y >= 0; --$y) {
                    $fillSession->getBlockIdAt($x, $y, $z, $id);
                    if($id == 0) {
                        $state = 0;
                    } elseif($state == 0) {
                        $state = 1;
                        $fillSession->setBlockAt($x, $y, $z, 2, 0); // Grass
                    } elseif($state < 5) { // 1 - 3
                        if($state == 3) {
                            $state += 2;
                        } else {
                            $state++;
                        }
                        $fillSession->setBlockAt($x, $y, $z, 3, 0);
                    } else {
                        $fillSession->setBlockAt($x, $y, $z, 1, 0);
                    }
                }
            }
        }

        $fillSession->reloadChunks($player->getLevelNonNull());

        /** @phpstan-var BlockArray $changes */
        $changes = $fillSession->getChanges();
        Canceller::getInstance()->addStep($player, $changes);

        return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
    }

    private function fix(BlockArray $list, Vector2 $vector2, int $minY, int $maxY, Level $level): void {
        $x = $vector2->getFloorX();
        $z = $vector2->getFloorY();

        $blockY = null;
        for($y = $minY; $y <= $maxY; ++$y) {
            if($level->getBlockAt($x, $y, $z, true, false)->getId() !== Block::AIR && ($blockY === null || $blockY < $y)) {
                $blockY = $y;
            }
        }

        if($blockY === null) return;

        for($y = $blockY; $y > $minY; --$y) {
            switch ($blockY-$y) {
                case 0:
                    $list->addBlock(new Vector3($x, $y, $z), BlockIds::GRASS, 0);
                    break;
                case 1:
                case 2:
                case 3:
                    if($level->getBlockAt($x, $y, $z, true, false)->getId() != BlockIds::AIR) {
                        $list->addBlock(new Vector3($x, $y, $z), BlockIds::DIRT, 0);
                    }
                    break;
                case 4:
                    if($level->getBlockAt($x, $y, $z, true, false)->getId() != BlockIds::AIR) {
                        $list->addBlock(new Vector3($x, $y, $z), rand(0, 1) ? BlockIds::DIRT : BlockIds::STONE, 0);
                    }
                    break;
                default:
                    if($level->getBlockAt($x, $y, $z, true, false)->getId() != BlockIds::AIR) {
                        $list->addBlock(new Vector3($x, $y, $z), BlockIds::STONE, 0);
                    }
            }
        }
    }
}