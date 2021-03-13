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

namespace czechpmdevs\buildertools\async\session;

use czechpmdevs\buildertools\blockstorage\OfflineSession;
use czechpmdevs\buildertools\ClipboardManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\ReaderTracker;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class OfflineSessionLoadTask extends AsyncTask {

    /** @var string */
    public $dataFolder;
    /** @var string */
    public $playerId;

    /** @var bool */
    public $exists = false;

    /** @var string */
    public $clipboard = "";
    /** @var Vector3|null */
    public $clipboardRelativePosition = null;
    /** @var bool */
    public $clipboardDuplicateDetectionEnabled = false;

    /** @var string */
    public $undoData = "";
    /** @var string */
    public $redoData = "";

    public function __construct(string $dataFolder, string $playerId) {
        $this->dataFolder = $dataFolder;
        $this->playerId = $playerId;
    }

    public function onRun() {
        if(!file_exists($path = $this->dataFolder . "offline_sessions" . DIRECTORY_SEPARATOR . $this->playerId . ".btsession")) {
            return;
        }

        $stream = new BigEndianNBTStream();
        $stream->buffer = file_get_contents($path);

        $data = new CompoundTag();
        $data->read($stream, new ReaderTracker(0));

        if($data->hasTag("Clipboard")) {
            $this->clipboard = $data->getString("Clipboard");
            $this->clipboardRelativePosition = new Vector3(...$data->getIntArray("ClipboardRelativePosition"));
            $this->clipboardDuplicateDetectionEnabled = (bool)$data->getByte("ClipboardDuplicateDetection");
            $this->exists = true;
        }

        if($data->hasTag("UndoData")) {
            $this->undoData = $data->getString("UndoData");
            $this->exists = true;
        }

        if($data->hasTag("RedoData")) {
            $this->redoData = $data->getString("RedoData");
            $this->exists = true;
        }

        unlink($path);
    }

    public function onCompletion(Server $server) {
        if($this->exists) {
            ClipboardManager::finishLoad($this->playerId, OfflineSession::saveData($this->playerId, $this->clipboard, $this->clipboardRelativePosition, $this->clipboardDuplicateDetectionEnabled, $this->undoData, $this->redoData));
        }
    }
}