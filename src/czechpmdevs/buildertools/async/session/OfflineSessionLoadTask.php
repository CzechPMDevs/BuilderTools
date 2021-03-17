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
use function file_get_contents;
use function unlink;

class OfflineSessionLoadTask extends AsyncTask {

    /** @var string */
    public string $dataFolder;
    /** @var string */
    public string $playerId;

    /** @var bool */
    public bool $exists = false;

    /** @var string */
    public string $clipboard = "";
    /** @var Vector3|null */
    public ?Vector3 $clipboardRelativePosition = null;
    /** @var bool */
    public bool $clipboardDuplicateDetectionEnabled = false;

    /** @var string */
    public string $undoData = "";
    /** @var string */
    public string $redoData = "";

    public function __construct(string $dataFolder, string $playerId) {
        $this->dataFolder = $dataFolder;
        $this->playerId = $playerId;
    }

    /** @noinspection PhpUnused */
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

    /** @noinspection PhpUnused */
    public function onCompletion(Server $server) {
        if($this->exists) {
            ClipboardManager::finishLoad($this->playerId, OfflineSession::createSession($this->playerId, $this->clipboard, $this->clipboardRelativePosition, $this->clipboardDuplicateDetectionEnabled, $this->undoData, $this->redoData));
        }
    }
}