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
use pocketmine\level\Position;
use pocketmine\Player;
use function array_key_exists;

class Selectors {

    /** @var Position[] */
    private static array $pos1 = [];
    /** @var Position[] */
    private static array $pos2 = [];

    /** @var Player[] */
    private static array $wandSelectors = [];

    /** @var array[] */
    private static array $drawingPlayers = [];
    /** @var Player[] */
    private static array $blockInfoPlayers = [];

    public static function addDrawingPlayer(Player $player, int $brush, int $mode, bool $fall): void {
        self::$drawingPlayers[$player->getName()] = [$brush, $mode, $fall];
    }

    public static function removeDrawingPlayer(Player $player): void {
        unset(self::$drawingPlayers[$player->getName()]);
    }

    public static function getDrawingPlayerBrush(Player $player): int {
        return self::$drawingPlayers[$player->getName()][0];
    }

    public static function getDrawingPlayerMode(Player $player): int {
        return self::$drawingPlayers[$player->getName()][1];
    }

    public static function getDrawingPlayerFall(Player $player): bool {
        return self::$drawingPlayers[$player->getName()][2];
    }

    public static function isDrawingPlayer(Player $player): bool {
        return array_key_exists($player->getName(), self::$drawingPlayers);
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
            self::$pos1[$player->getName()] = $position;
        } else {
            self::$pos2[$player->getName()] = $position;
        }

        $pos1 = self::$pos1[$player->getName()] ?? null;
        $pos2 = self::$pos2[$player->getName()] ?? null;

        if($pos1 === null || $pos2 === null) {
            return null;
        }

        if($pos1->getLevel() === null || $pos2->getLevel() === null) {
            return null;
        }

        if($pos1->getLevel()->isClosed() || $pos2->getLevel()->isClosed()) {
            return null;
        }

        if($pos1->getLevel()->getId() != $pos2->getLevel()->getId()) {
            return null;
        }

        return Math::selectionSize($pos1, $pos2);
    }

    public static function getPosition(Player $player, int $pos): ?Position {
        if($pos == 1) {
            return self::$pos1[$player->getName()];
        }
        if($pos == 2) {
            return self::$pos2[$player->getName()];
        }

        return null;
    }

    public static function isSelected(int $pos, Player $player): bool {
        if($pos == 1) {
            return array_key_exists($player->getName(), self::$pos1);
        }
        if($pos == 2) {
            return array_key_exists($player->getName(), self::$pos2);
        }

        return false;
    }

    public static function switchWandSelector(Player $player): void {
        if(array_key_exists($player->getName(), self::$wandSelectors)) {
            unset(self::$wandSelectors[$player->getName()]);
        } else {
            self::$wandSelectors[$player->getName()] = $player;
        }
    }

    public static function switchBlockInfoSelector(Player $player): void {
        if(array_key_exists($player->getName(), self::$blockInfoPlayers)) {
            unset(self::$blockInfoPlayers[$player->getName()]);
        } else {
            self::$blockInfoPlayers[$player->getName()] = $player;
        }
    }

    public static function isWandSelector(Player $player): bool {
        return array_key_exists($player->getName(), self::$wandSelectors);
    }

    public static function isBlockInfoPlayer(Player $player): bool {
        return array_key_exists($player->getName(), self::$blockInfoPlayers);
    }

    public static function unloadPlayer(Player $player): void {
        unset(self::$wandSelectors[$player->getName()]);
        unset(self::$blockInfoPlayers[$player->getName()]);
        unset(self::$drawingPlayers[$player->getName()]);

        unset(self::$pos1[$player->getName()]);
        unset(self::$pos2[$player->getName()]);
    }
}