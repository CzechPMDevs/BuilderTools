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

use pocketmine\level\Position;
use pocketmine\Player;

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

    public static function addDrawingPlayer(Player $player, int $brush, int $mode, bool $fall) {
        self::$drawingPlayers[strtolower($player->getName())] = [$brush, $mode, $fall];
    }

    public static function removeDrawingPlayer(Player $player) {
        unset(self::$drawingPlayers[strtolower($player->getName())]);
    }

    public static function getDrawingPlayerBrush(Player $player): int {
        return self::$drawingPlayers[strtolower($player->getName())][0];
    }

    public static function getDrawingPlayerMode(Player $player): int {
        return self::$drawingPlayers[strtolower($player->getName())][1];
    }

    public static function getDrawingPlayerFall(Player $player): bool {
        return self::$drawingPlayers[strtolower($player->getName())][2];
    }

    public static function isDrawingPlayer(Player $player): bool {
        return (bool)isset(self::$drawingPlayers[strtolower($player->getName())]);
    }

    public static function addSelector(Player $player, int $pos, Position $position) {
        if($pos == 1) {
            self::$pos1[strtolower($player->getName())] = $position;
        }
        if($pos == 2) {
            self::$pos2[strtolower($player->getName())] = $position;
        }
    }

    public static function getPosition(Player $player, int $pos): ?Position {
        if($pos == 1) {
            return self::$pos1[strtolower($player->getName())];
        }
        if($pos == 2) {
            return self::$pos2[strtolower($player->getName())];
        }

        return null;
    }

    public static function isSelected(int $pos, Player $player): bool {
        if($pos == 1) {
            return (bool)isset(self::$pos1[strtolower($player->getName())]);
        }
        if($pos == 2) {
            return (bool)isset(self::$pos2[strtolower($player->getName())]);
        }

        return false;
    }

    public static function switchWandSelector(Player $player) {
        if(isset(self::$wandSelectors[strtolower($player->getName())])) {
            unset(self::$wandSelectors[strtolower($player->getName())]);
        }
        else {
            self::$wandSelectors[strtolower($player->getName())] = $player;
        }
    }

    public static function switchBlockInfoSelector(Player $player) {
        if(isset(self::$blockInfoPlayers[strtolower($player->getName())])) {
            unset(self::$blockInfoPlayers[strtolower($player->getName())]);
        }
        else {
            self::$blockInfoPlayers[strtolower($player->getName())] = $player;
        }
    }

    public static function isWandSelector(Player $player): bool {
        return (bool)isset(self::$wandSelectors[strtolower($player->getName())]);
    }

    public static function isBlockInfoPlayer(Player $player): bool {
        return (bool)isset(self::$blockInfoPlayers[strtolower($player->getName())]);
    }
}