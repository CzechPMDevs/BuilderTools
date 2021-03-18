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

use InvalidArgumentException;
use pocketmine\player\Player;
use pocketmine\world\Position;
use function strtolower;

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
        self::$drawingPlayers[strtolower($player->getName())] = [$brush, $mode, $fall];
    }

    public static function removeDrawingPlayer(Player $player): void {
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
        return isset(self::$drawingPlayers[strtolower($player->getName())]);
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
            self::$pos1[strtolower($player->getName())] = $position;
        } else {
            self::$pos2[strtolower($player->getName())] = $position;
        }

        $pos1 = self::$pos1[strtolower($player->getName())] ?? null;
        $pos2 = self::$pos2[strtolower($player->getName())] ?? null;

        if($pos1 === null || $pos2 === null) {
            return null;
        }

        if($pos1->getWorld() === null || $pos2->getWorld() === null) {
            return null;
        }

        if($pos1->getWorld()->isClosed() || $pos2->getWorld()->isClosed()) {
            return null;
        }

        if($pos1->getWorld()->getId() != $pos2->getWorld()->getId()) {
            return null;
        }

        $vec = $pos2->subtract($pos1->getX(), $pos1->getY(), $pos1->getZ())->abs()->add(1, 1, 1);
        return (int)($vec->getX() * $vec->getY() * $vec->getZ());
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
            return isset(self::$pos1[strtolower($player->getName())]);
        }
        if($pos == 2) {
            return isset(self::$pos2[strtolower($player->getName())]);
        }

        return false;
    }

    public static function switchWandSelector(Player $player): void {
        if(isset(self::$wandSelectors[strtolower($player->getName())])) {
            unset(self::$wandSelectors[strtolower($player->getName())]);
        }
        else {
            self::$wandSelectors[strtolower($player->getName())] = $player;
        }
    }

    public static function switchBlockInfoSelector(Player $player): void {
        if(isset(self::$blockInfoPlayers[strtolower($player->getName())])) {
            unset(self::$blockInfoPlayers[strtolower($player->getName())]);
        }
        else {
            self::$blockInfoPlayers[strtolower($player->getName())] = $player;
        }
    }

    public static function isWandSelector(Player $player): bool {
        return isset(self::$wandSelectors[strtolower($player->getName())]);
    }

    public static function isBlockInfoPlayer(Player $player): bool {
        return isset(self::$blockInfoPlayers[strtolower($player->getName())]);
    }
}