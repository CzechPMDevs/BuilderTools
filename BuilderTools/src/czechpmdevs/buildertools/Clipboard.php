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

namespace czechpmdevs\buildertools;

use czechpmdevs\buildertools\editors\blockstorage\ClipboardData;
use czechpmdevs\buildertools\editors\object\EditorStep;
use pocketmine\Player;

/**
 * Class Clipboard
 * @package czechpmdevs\buildertools
 */
class Clipboard {

    /** @var Clipboard[] $clipboards */
    private static $clipboards = [];

    /** @var string $player */
    private $player;

    /** @var EditorStep[] $undoQueue */
    private $undoQueue = [];

    /** @var EditorStep[] $redoQueue */
    private $redoQueue = [];

    /**
     * Clipboard constructor.
     * @param Player $player
     */
    public function __construct(Player $player) {
        $this->player = $player->getName();
    }

    /**
     * Returns step which can bee cancelled
     *
     * @api
     *
     * @param bool $addToRedo
     * @return EditorStep|null
     */
    public function getLastStep(bool $addToRedo = true): ?EditorStep {
        $targetClipboard = array_pop($this->undoQueue);

        if(!is_null($targetClipboard) && $addToRedo) {
            $this->redoQueue[] = $targetClipboard;
        }

        return $targetClipboard;
    }

    /**
     * Adds step to //undo queue
     *
     * @api
     *
     * @param ClipboardData $backwardsClipboard
     */
    public function addStep(ClipboardData $backwardsClipboard) {
        $this->undoQueue[] = $backwardsClipboard;
    }

    /**
     * Returns step which cancels undo
     *
     * @api
     *
     * @param bool $addToUndo
     * @return ClipboardData|null
     */
    public function getNextStep(bool $addToUndo = true): ?ClipboardData {
        $targetClipboard = array_pop($this->redoQueue);

        if(!is_null($targetClipboard) && $addToUndo) {
            $this->undoQueue[] = $targetClipboard;
        }

        return $targetClipboard;
    }

    /**
     * Adds step to //redo queue
     *
     * @api
     *
     * @param ClipboardData $clipboard
     */
    public function addCancelledStep(ClipboardData $clipboard) {
        $this->redoQueue[] = $clipboard;
    }

    /**
     * Returns clipboard of player
     *
     * @api
     *
     * @param Player $player
     * @return Clipboard
     */
    public static function getClipboard(Player $player) {
        return self::$clipboards[$player->getName()] ?? self::$clipboards[$player->getName()] = new Clipboard($player);
    }
}