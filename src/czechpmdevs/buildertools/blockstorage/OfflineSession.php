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
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
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
            /** @phpstan-var SelectionData $clipboard */
            $clipboard = ClipboardManager::getClipboard($player);

            $clipboard->compress();

            $nbt->setTag(new CompoundTag("Clipboard", [
                new ByteArrayTag("Coordinates", $clipboard->compressedCoords),
                new ByteArrayTag("Blocks", $clipboard->compressedBlocks),
                new ByteArrayTag("RelativePosition", $clipboard->compressedPlayerPosition)
                ]
            ));

            unset(ClipboardManager::$clipboards[$player->getName()]);
        }

        $stream = new BigEndianNBTStream();
        file_put_contents(BuilderTools::getInstance()->getDataFolder() . "sessions/{$player->getName()}.dat", $stream->writeCompressed($nbt));

        unset($stream, $nbt);

        BuilderTools::getInstance()->getLogger()->debug("Session for {$player->getName()} saved in " . round(microtime(true) - $time , 3) . " seconds (Saved " . round((memory_get_usage() - $memory) / (1024 ** 2), 3) . "Mb ram)");
    }

    public static function loadPlayerSession(Player $player): void {
        if(!file_exists($path = BuilderTools::getInstance()->getDataFolder() . "sessions/{$player->getName()}.dat")) {
            return;
        }

        $stream = new BigEndianNBTStream();

        $buffer = file_get_contents($path);
        if(!$buffer || !@unlink($path)) {
            return;
        }

        /** @phpstan-var CompoundTag|null $nbt */
        $nbt = $stream->readCompressed($buffer);

        if($nbt === null) {
            return;
        }

        // Clipboard
        if($nbt->hasTag("Clipboard")) {
            /** @phpstan-var CompoundTag $clipboardTag */
            $clipboardTag = $nbt->getCompoundTag("Clipboard");

            $clipboard = new SelectionData();
            $clipboard->compressedCoords = $clipboardTag->getByteArray("Coordinates");
            $clipboard->compressedBlocks = $clipboardTag->getByteArray("Blocks");
            $clipboard->compressedPlayerPosition = $clipboardTag->getByteArray("RelativePosition");

            $clipboard->decompress();

            ClipboardManager::saveClipboard($player, $clipboard);
        }
    }
}