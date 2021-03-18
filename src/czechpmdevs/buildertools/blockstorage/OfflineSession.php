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

namespace czechpmdevs\buildertools\blockstorage;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\ClipboardManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function memory_get_usage;
use function microtime;
use function unlink;

final class OfflineSession {

    public static function savePlayerSession(Player $player): void {
        $time = microtime(true);
        $memory = memory_get_usage();

        $nbt = new CompoundTag();

        // Clipboard
        if(ClipboardManager::hasClipboardCopied($player)) {
            /** @var SelectionData $clipboard */
            $clipboard = ClipboardManager::getClipboard($player);
            /** @var Vector3 $playerPosition */
            $playerPosition = $clipboard->getPlayerPosition();

            $nbt->setTag("Clipboard", (new CompoundTag())
                ->setIntArray("Blocks", $clipboard->blocks)
                ->setIntArray("Coordinates", $clipboard->coords)
                ->setIntArray("RelativePosition", [
                    $playerPosition->getFloorX(),
                    $playerPosition->getFloorY(),
                    $playerPosition->getFloorZ()
                ])
            );

            unset(ClipboardManager::$clipboards[$player->getName()]);
        }

        $serializer = new BigEndianNbtSerializer();
        file_put_contents(BuilderTools::getInstance()->getDataFolder() . "sessions/{$player->getName()}.dat", $serializer->write(new TreeRoot($nbt)));

        unset($serializer, $nbt);

        BuilderTools::getInstance()->getLogger()->debug("Session for {$player->getName()} saved in " . round(microtime(true) - $time , 3) . " seconds (Saved " . round((memory_get_usage() - $memory) / (1024 * 1024), 3) . "Mb ram)");
    }

    public static function loadPlayerSession(Player $player): void {
        if(!file_exists($path = BuilderTools::getInstance()->getDataFolder() . "sessions/{$player->getName()}.dat")) {
            return;
        }

        $serializer = new BigEndianNbtSerializer();

        $buffer = file_get_contents($path);
        if(!$buffer || !@unlink($path)) {
            return;
        }

        /** @var CompoundTag|null $nbt */
        $nbt = $serializer->read($buffer)->getTag();

        if($nbt === null) {
            return;
        }

        if($nbt->hasTag("Clipboard")) {
            /** @var CompoundTag $clipboardTag */
            $clipboardTag = $nbt->getCompoundTag("Clipboard");

            $clipboard = new SelectionData();
            $clipboard->coords = $clipboardTag->getIntArray("Coordinates");
            $clipboard->blocks = $clipboardTag->getIntArray("Blocks");
            $clipboard->setPlayerPosition(new Vector3(...$clipboardTag->getIntArray("RelativePosition")));

            ClipboardManager::saveClipboard($player, $clipboard);
        }
    }
}