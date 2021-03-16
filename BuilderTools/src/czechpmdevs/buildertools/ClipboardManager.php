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

namespace czechpmdevs\buildertools;

use czechpmdevs\buildertools\blockstorage\OfflineSession;
use czechpmdevs\buildertools\blockstorage\SelectionData;
use pocketmine\Player;
use pocketmine\Server;

class ClipboardManager {

    /** @var SelectionData[] */
    public static array $clipboards;

    /** @var SelectionData[] */
    public static array $undoData;
    /** @var SelectionData[] */
    public static array $redoData;

    public static function getClipboard(Player $player): ?SelectionData {
        return clone self::$clipboards[$player->getName()] ?? null;
    }

    public static function hasClipboardCopied(Player $player): bool {
        return isset(self::$clipboards[$player->getName()]);
    }

    public static function saveClipboard(Player $player, SelectionData $data): void {
        self::$clipboards[$player->getName()] = $data;
    }

    /**
     * TODO
     * @noinspection PhpUnused
     */
    public static function loadPlayerSession(Player $player) {

    }

    /**
     * TODO
     * @noinspection PhpUnused
     */
    public static function unloadPlayerSession(Player $player) {
        $offlineSession = OfflineSession::create($player);
        if(self::hasClipboardCopied($player)) {
            $offlineSession->setClipboard(self::$clipboards[$player->getName()]);
        }
        // TODO - Undo & Redo

        $offlineSession->save();
    }

    /**
     * TODO
     * @internal
     */
    public static function finishLoad(string $playerId, OfflineSession $session) {
        if(Server::getInstance()->getPlayerExact($playerId) === null) {
            $session->save();
        }


    }
}