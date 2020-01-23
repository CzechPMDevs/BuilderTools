<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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

namespace czechpmdevs\buildertools\editors\object;

use czechpmdevs\buildertools\editors\blockstorage\ClipboardData;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Filler;
use pocketmine\Player;

/**
 * Class EditorStep
 * @package czechpmdevs\buildertools\editors\object
 */
class EditorStep {

    public const ID_FILL = 0;
    public const ID_ROTATE = 1;

    /** @var int $id */
    private $id;

    /** @var ClipboardData $clipboard */
    private $clipboard;

    /** @var array $customData */
    private $customData = [];

    /**
     * EditorStep constructor.
     *
     * @param ClipboardData $clipboard
     * @param int $stepId
     * @param array $customData
     */
    public function __construct(ClipboardData $clipboard, int $stepId = 0, array $customData = []) {
        $this->clipboard = $clipboard;
        $this->customData = $customData;
        $this->id = $stepId;
    }

    /**
     * @return ClipboardData
     */
    public function getClipboard(): ClipboardData {
        return $this->clipboard;
    }

    /**
     * @return array
     */
    public function getCustomData(): array {
        return $this->customData;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function useOn(Player $player): bool {
        /** @var Filler $filler */
        $filler = Editor::getEditor(Editor::FILLER);

        switch ($this->id) {
            case self::ID_FILL:
                $filler->fill($player, $this->getClipboard());
                return true;
            case self::ID_ROTATE:
                return false;
        }

        return false;
    }
}