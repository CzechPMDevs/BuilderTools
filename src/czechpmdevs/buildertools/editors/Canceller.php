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
use czechpmdevs\buildertools\ClipboardManager;
use czechpmdevs\buildertools\editors\object\EditorResult;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class Canceller {
    use SingletonTrait;

    public function addStep(Player $player, BlockArray $blocks): void {
        ClipboardManager::saveUndo($player, $blocks);
    }

    public function undo(Player $player): EditorResult {
        $error = function (): EditorResult {
            return EditorResult::error("There are not any actions to undo");
        };

        if(!ClipboardManager::hasActionToUndo($player)) {
            return $error();
        }

        $blockList = ClipboardManager::getNextUndoAction($player);
        if($blockList === null) {
            return $error();
        }

        return Filler::getInstance()->fill($player, $blockList, null, false, true);
    }

    public function addRedo(Player $player, BlockArray $blocks): void {
        ClipboardManager::saveUndo($player, $blocks);
    }

    public function redo(Player $player): EditorResult {
        $error = function (): EditorResult {
            return EditorResult::error("There are not any actions to redo");
        };

        if(ClipboardManager::hasActionToRedo($player)) {
            return $error();
        }

        $blockList = ClipboardManager::getNextRedoAction($player);
        if($blockList === null) {
            return $error();
        }

        return Filler::getInstance()->fill($player, $blockList);
    }
}