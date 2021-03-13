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
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\EditorResult;
use pocketmine\Player;

class Canceller extends Editor {

    /** @var BlockArray[][] */
    public $undoData = [];
    /** @var BlockArray[][] */
    public $redoData = [];

    public function getName(): string {
        return "Canceller";
    }

    public function addStep(Player $player, BlockArray $blocks) {
        $this->undoData[$player->getName()][] = $blocks;
    }

    public function undo(Player $player): EditorResult {
        if(!isset($this->undoData[$player->getName()]) || count($this->undoData[$player->getName()]) == 0) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cThere are not actions to undo!");
            return new EditorResult(0, 0, true);
        }

        $blockList = array_pop($this->undoData[$player->getName()]);

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(self::FILLER);
        return $filler->fill($player, $blockList, null, false, true);
    }

    public function addRedo(Player $player, BlockArray $blocks) {
        $this->redoData[$player->getName()][] = $blocks;
    }

    public function redo(Player $player): EditorResult {
        if(!isset($this->redoData[$player->getName()]) || count($this->redoData[$player->getName()]) == 0) {
            $player->sendMessage(BuilderTools::getPrefix() . "§cThere are not actions to redo!");
            return new EditorResult(0, 0, true);
        }

        $blockList = array_pop($this->redoData[$player->getName()]);

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(self::FILLER);
        return $filler->fill($player, $blockList);
    }
}