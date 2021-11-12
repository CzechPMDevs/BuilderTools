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

use czechpmdevs\buildertools\math\Math;
use InvalidArgumentException;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\Position;
use function array_key_exists;

class Selectors {

	/** @var Position[] */
	private static array $pos1 = [];
	/** @var Position[] */
	private static array $pos2 = [];

	/** @var Player[] */
	private static array $wandSelectors = [];

	/** @var array<string, array{0: int, 1: int, 2: bool}> */
	private static array $drawingPlayers = [];
	/** @var Player[] */
	private static array $blockInfoPlayers = [];

	public static function addDrawingPlayer(Player $player, int $brush, int $mode, bool $fall): void {
		Selectors::$drawingPlayers[$player->getName()] = [$brush, $mode, $fall];
	}

	public static function removeDrawingPlayer(Player $player): void {
		unset(Selectors::$drawingPlayers[$player->getName()]);
	}

	public static function getDrawingPlayerBrush(Player $player): int {
		return Selectors::$drawingPlayers[$player->getName()][0];
	}

	public static function getDrawingPlayerMode(Player $player): int {
		return Selectors::$drawingPlayers[$player->getName()][1];
	}

	public static function getDrawingPlayerFall(Player $player): bool {
		return Selectors::$drawingPlayers[$player->getName()][2];
	}

	public static function isDrawingPlayer(Player $player): bool {
		return array_key_exists($player->getName(), Selectors::$drawingPlayers);
	}

	/**
	 * @return int|null If not null, returns count of blocks in selection
	 */
	public static function addSelector(Player $player, int $pos, Position $position): ?int {
		if($pos != 1 && $pos != 2) {
			throw new InvalidArgumentException("Player can select only two positions");
		}
		if(!$position->equals($position->ceil())) {
			throw new InvalidArgumentException("Position coordinates must be integer type.");
		}

		if($pos == 1) {
			Selectors::$pos1[$player->getName()] = $position;
		} else {
			Selectors::$pos2[$player->getName()] = $position;
		}

		$pos1 = Selectors::$pos1[$player->getName()] ?? null;
		$pos2 = Selectors::$pos2[$player->getName()] ?? null;

		if($pos1 === null || $pos2 === null) {
			return null;
		}

		if(!$pos1->getWorld()->isLoaded() || !$pos2->getWorld()->isLoaded()) {
			return null;
		}

		if($pos1->getWorld()->getId() != $pos2->getWorld()->getId()) {
			return null;
		}

		return Math::selectionSize($pos1, $pos2);
	}

	public static function getPosition(Player $player, int $pos): Position {
		if($pos == 1) {
			return Selectors::$pos1[$player->getName()];
		}
		if($pos == 2) {
			return Selectors::$pos2[$player->getName()];
		}

		throw new AssumptionFailedError("{$player->getDisplayName()} does not have selected required position");
	}

	public static function isSelected(int $pos, Player $player): bool {
		if($pos == 1) {
			return array_key_exists($player->getName(), Selectors::$pos1);
		}
		if($pos == 2) {
			return array_key_exists($player->getName(), Selectors::$pos2);
		}

		return false;
	}

	public static function switchWandSelector(Player $player): void {
		if(array_key_exists($player->getName(), Selectors::$wandSelectors)) {
			unset(Selectors::$wandSelectors[$player->getName()]);
		} else {
			Selectors::$wandSelectors[$player->getName()] = $player;
		}
	}

	public static function switchBlockInfoSelector(Player $player): void {
		if(array_key_exists($player->getName(), Selectors::$blockInfoPlayers)) {
			unset(Selectors::$blockInfoPlayers[$player->getName()]);
		} else {
			Selectors::$blockInfoPlayers[$player->getName()] = $player;
		}
	}

	public static function isWandSelector(Player $player): bool {
		return array_key_exists($player->getName(), Selectors::$wandSelectors);
	}

	public static function isBlockInfoPlayer(Player $player): bool {
		return array_key_exists($player->getName(), Selectors::$blockInfoPlayers);
	}

	public static function unloadPlayer(Player $player): void {
		unset(Selectors::$wandSelectors[$player->getName()]);
		unset(Selectors::$blockInfoPlayers[$player->getName()]);
		unset(Selectors::$drawingPlayers[$player->getName()]);

		unset(Selectors::$pos1[$player->getName()]);
		unset(Selectors::$pos2[$player->getName()]);
	}
}