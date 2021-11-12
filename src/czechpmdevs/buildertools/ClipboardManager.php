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

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\SelectionData;
use pocketmine\player\Player;
use function array_key_exists;
use function array_pop;

class ClipboardManager {

	/** @var SelectionData[] */
	public static array $clipboards = [];

	/** @var BlockArray[][] */
	public static array $undoData = [];
	/** @var BlockArray[][] */
	public static array $redoData = [];

	public static function getClipboard(Player $player): ?SelectionData {
		return array_key_exists($player->getName(), self::$clipboards) ? clone self::$clipboards[$player->getName()] : null;
	}

	public static function hasClipboardCopied(Player $player): bool {
		return array_key_exists($player->getName(), self::$clipboards);
	}

	public static function saveClipboard(Player $player, SelectionData $data): void {
		self::$clipboards[$player->getName()] = $data;
	}

	public static function getNextUndoAction(Player $player): ?BlockArray {
		return array_pop(self::$undoData[$player->getName()]);
	}

	public static function hasActionToUndo(Player $player): bool {
		return array_key_exists($player->getName(), self::$undoData) && !empty(self::$undoData[$player->getName()]);
	}

	public static function saveUndo(Player $player, BlockArray $array): void {
		self::$undoData[$player->getName()][] = $array;
	}

	public static function getNextRedoAction(Player $player): ?BlockArray {
		return array_pop(self::$redoData[$player->getName()]);
	}

	public static function hasActionToRedo(Player $player): bool {
		return array_key_exists($player->getName(), self::$redoData) && !empty(self::$redoData[$player->getName()]);
	}

	public static function saveRedo(Player $player, BlockArray $array): void {
		self::$redoData[$player->getName()][] = $array;
	}
}