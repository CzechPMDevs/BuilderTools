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

use pocketmine\level\Position;
use pocketmine\Player;

/**
 * Class Selectors
 * @package buildertools
 */
class Selectors {

    /** @var Position[] $pos1 */
    private static $pos1 = [];

    /** @var Position[] $pos2 */
    private static $pos2 = [];

    /** @var Player[] $wandSelectors */
    private static $wandSelectors = [];

    /** @var int[] $drawingPlayers */
    private static $drawingPlayers = [];

    /** @var Player[] $blockInfoPlayers */
    private static $blockInfoPlayers = [];

    /**
     * @param Player $player
     * @param int $brush
     */
    public static function addDrawingPlayer(Player $player, int $brush, int $mode, bool $fall) {
        self::$drawingPlayers[strtolower($player->getName())] = [$brush, $mode, $fall];
    }

    /**
     * @param Player $player
     */
    public static function removeDrawnigPlayer(Player $player) {
        unset(self::$drawingPlayers[strtolower($player->getName())]);
    }

    /**
     * @param Player $player
     * @return int
     */
    public static function getDrawingPlayerBrush(Player $player) {
        return self::$drawingPlayers[strtolower($player->getName())][0];
    }

    /**
     * @param Player $player
     * @return int
     */
    public static function getDrawingPlayerMode(Player $player) {
        return self::$drawingPlayers[strtolower($player->getName())][1];
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function getDrawingPlayerFall(Player $player) {
        return self::$drawingPlayers[strtolower($player->getName())][2];
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function isDrawingPlayer(Player $player) {
        return (bool)isset(self::$drawingPlayers[strtolower($player->getName())]);
    }

    /**
     * @param Player $player
     * @param int $pos
     * @param Position $position
     */
    public static function addSelector(Player $player, int $pos, Position $position) {
        if($pos == 1) {
            self::$pos1[strtolower($player->getName())] = $position;
        }
        if($pos == 2) {
            self::$pos2[strtolower($player->getName())] = $position;
        }
    }

    /**
     * @param Player $player
     * @param int $pos
     * @return Position $position
     */
    public static function getPosition(Player $player, int $pos):Position {
        if($pos == 1) {
            return self::$pos1[strtolower($player->getName())];
        }
        if($pos == 2) {
            return self::$pos2[strtolower($player->getName())];
        }
        return null;
    }

    /**
     * @param int $pos
     * @param Player $player
     * @return bool
     */
    public static function isSelected(int $pos, Player $player):bool {
        if($pos == 1) {
            return (bool)isset(self::$pos1[strtolower($player->getName())]);
        }
        if($pos == 2) {
            return (bool)isset(self::$pos2[strtolower($player->getName())]);
        }
        return false;
    }

    /**
     * @param Player $player
     */
    public static function switchWandSelector(Player $player) {
        if(isset(self::$wandSelectors[strtolower($player->getName())])) {
            unset(self::$wandSelectors[strtolower($player->getName())]);
        }
        else {
            self::$wandSelectors[strtolower($player->getName())] = $player;
        }
    }

    /**
     * @param Player $player
     */
    public static function switchBlockInfoSelector(Player $player) {
        if(isset(self::$blockInfoPlayers[strtolower($player->getName())])) {
            unset(self::$blockInfoPlayers[strtolower($player->getName())]);
        }
        else {
            self::$blockInfoPlayers[strtolower($player->getName())] = $player;
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function isWandSelector(Player $player):bool {
        return (bool)isset(self::$wandSelectors[strtolower($player->getName())]);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function isBlockInfoPlayer(Player $player) {
        return (bool)isset(self::$blockInfoPlayers[strtolower($player->getName())]);
    }
}