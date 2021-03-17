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
use pocketmine\utils\SingletonTrait;
use function array_pop;

class Canceller {
    use SingletonTrait;

    /** @var BlockArray[][] */
    public array $undoData = [];
    /** @var BlockArray[][] */
    public array $redoData = [];

    public function addStep(Player $player, BlockArray $blocks): void {
        $this->undoData[$player->getName()][] = $blocks;
    }

    public function undo(Player $player): EditorResult {
        $error = function () use ($player): EditorResult {
            return EditorResult::error("There are not any actions to undo");
        };

        if(!isset($this->undoData[$player->getName()])) {
            return $error();
        }

        $blockList = array_pop($this->undoData[$player->getName()]);
        if($blockList === null) {
            return $error();
        }

        return Filler::getInstance()->fill($player, $blockList, null, false, true);
    }

    public function addRedo(Player $player, BlockArray $blocks): void {
        $this->redoData[$player->getName()][] = $blocks;
    }

    public function redo(Player $player): EditorResult {
        $error = function () use ($player): EditorResult {
            return EditorResult::error("There are not any actions to redo");
        };

        if(!isset($this->redoData[$player->getName()])) {
            return $error();
        }

        $blockList = array_pop($this->redoData[$player->getName()]);
        if($blockList === null) {
            return $error();
        }

        return Filler::getInstance()->fill($player, $blockList);
    }
}