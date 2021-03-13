<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\async\session;

use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\AsyncTask;

class OfflineSessionSaveTask extends AsyncTask {

    /** @var string */
    public $dataFolder;
    /** @var string */
    public $playerId;

    /** @var string */
    public $clipboard;
    /** @var Vector3|null */
    public $clipboardRelativePosition;
    /** @var bool */
    public $clipboardDuplicateDetectionEnabled;

    /** @var string */
    public $undoData;
    /** @var string */
    public $redoData;

    public function __construct(string $dataFolder, string $playerId, string $clipboard, ?Vector3 $clipboardRelativePosition, bool $clipboardDuplicateDetectionEnabled, string $undoData, string $redoData) {
        $this->dataFolder = $dataFolder;
        $this->playerId = $playerId;
        $this->clipboard = $clipboard;
        $this->clipboardRelativePosition = $clipboardRelativePosition;
        $this->clipboardDuplicateDetectionEnabled = $clipboardDuplicateDetectionEnabled;
        $this->undoData = $undoData;
        $this->redoData = $redoData;
    }

    public function onRun() {
        $data = new CompoundTag();
        $save = false;

        if($this->clipboard != "") {
            $data->setString("Clipboard", $this->clipboard);
            $data->setIntArray("ClipboardRelativePosition", [
                $this->clipboardRelativePosition->getX(),
                $this->clipboardRelativePosition->getY(),
                $this->clipboardRelativePosition->getZ()
            ]);
            $data->setByte("ClipboardDuplicateDetection", (int)$this->clipboardDuplicateDetectionEnabled);

            $save = true;
        }

        if($this->undoData != "") {
            $data->setString("UndoData", $this->undoData);

            $save = true;
        }

        if($this->redoData != "") {
            $data->setString("RedoData", $this->redoData);

            $save = true;
        }

        if(!$save) {
            return;
        }

        $stream = new BigEndianNBTStream();
        $data->write($stream);

        file_put_contents($this->dataFolder . "offline_data" . DIRECTORY_SEPARATOR . $this->playerId . ".btsession", $stream->buffer);
    }
}