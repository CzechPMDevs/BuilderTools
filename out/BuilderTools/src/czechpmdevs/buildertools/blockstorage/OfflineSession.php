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

use czechpmdevs\buildertools\async\session\OfflineSessionLoadTask;
use czechpmdevs\buildertools\async\session\OfflineSessionSaveTask;
use czechpmdevs\buildertools\BuilderTools;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

final class OfflineSession {

    /** @var string */
    private $playerId;

    /** @var string */
    private $clipboard = "";
    /** @var Vector3|null */
    private $clipboardRelativePos = null;
    /** @var bool */
    private $clipboardDuplicateDetectionEnabled = false;

    /** @var string */
    private $undoData = "";
    /** @var string */
    private $redoData = "";

    private function __construct() {}

    /**
     * @return $this
     */
    public function setClipboard(SelectionData $data): OfflineSession {
        if($data->getLevel() === null) {
            return $this;
        }

        $this->clipboard = $data->buffer;
        $this->clipboardRelativePos = $data->getPlayerPosition()->asVector3();
        $this->clipboardDuplicateDetectionEnabled = $data->detectingDuplicates();
        return $this;
    }

    public function getClipboard(): ?SelectionData {
        if($this->clipboard == "") {
            return null;
        }

        $clipboard = new SelectionData($this->clipboardDuplicateDetectionEnabled);
        $clipboard->setPlayerPosition($this->clipboardRelativePos);
        $clipboard->buffer = $this->clipboard;

        return $clipboard;
    }

    /**
     * @return $this
     */
    public function setUndoData(array $undoData): OfflineSession {
        $this->undoData = serialize(array_map(function (BlockArray $blockArray) {
            return [
                $blockArray->buffer,
                $blockArray->detectingDuplicates(),
                $blockArray->getLevel() === null ? null : $blockArray->getLevel()->getFolderName()
            ];
        }, $undoData));

        return $this;
    }

    /**
     * @return BlockArray[]
     */
    public function getUndoData(): array {
        return array_filter(array_map(function (array $data): ?BlockArray {
            $blockArray = new BlockArray($data[1]);
            $blockArray->buffer = $data[0];

            if($data[2] === null || (!Server::getInstance()->isLevelGenerated($data[2])) || (!Server::getInstance()->isLevelLoaded($data[2]))) {
                return null;
            }
            $blockArray->setLevel(Server::getInstance()->getLevel($data[2]));

            return $blockArray;
        }, unserialize($this->undoData)), function (?BlockArray $blockArray): bool {
            return $blockArray !== null;
        });
    }

    /**
     * @return $this
     */
    public function setRedoData(array $redoData): OfflineSession {
        $this->redoData = serialize(array_map(function (BlockArray $blockArray) {
            return [
                $blockArray->buffer,
                $blockArray->detectingDuplicates(),
                $blockArray->getLevel() === null ? null : $blockArray->getLevel()->getFolderName()
            ];
        }, $redoData));

        return $this;
    }

    /**
     * @return BlockArray[]
     */
    public function getRedoData(): array {
        return array_filter(array_map(function (array $data): ?BlockArray {
            $blockArray = new BlockArray($data[1]);
            $blockArray->buffer = $data[0];

            if($data[2] === null || (!Server::getInstance()->isLevelGenerated($data[2])) || (!Server::getInstance()->isLevelLoaded($data[2]))) {
                return null;
            }
            $blockArray->setLevel(Server::getInstance()->getLevel($data[2]));

            return $blockArray;
        }, unserialize($this->redoData)), function (?BlockArray $blockArray): bool {
            return $blockArray !== null;
        });
    }

    public function save() {
        Server::getInstance()->getAsyncPool()->submitTask(new OfflineSessionSaveTask(
            BuilderTools::getInstance()->getDataFolder(),
            $this->playerId,
            $this->clipboard,
            $this->clipboardRelativePos,
            $this->clipboardDuplicateDetectionEnabled,
            $this->undoData,
            $this->redoData
        ));
    }

    /**
     * @internal
     */
    public static function createSession(string $playerId, string $clipboard, ?Vector3 $clipboardRelativePos, bool $clipboardDuplicateDetectionEnabled, string $undoData, string $redoData): OfflineSession {
        $session = new OfflineSession();
        $session->playerId = $playerId;
        $session->clipboard = $clipboard;
        $session->clipboardRelativePos = $clipboardRelativePos;
        $session->clipboardDuplicateDetectionEnabled = $clipboardDuplicateDetectionEnabled;
        $session->undoData = $undoData;
        $session->redoData = $redoData;

        return $session;
    }

    public static function load(Player $player) {
        $player->getServer()->getAsyncPool()->submitTask(new OfflineSessionLoadTask(BuilderTools::getInstance()->getDataFolder(), $player->getName()));
    }

    public static function create(Player $player): OfflineSession {
        $session = new OfflineSession();
        $session->playerId = $player->getName();

        return $session;
    }
}