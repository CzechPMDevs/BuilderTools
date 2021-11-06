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

namespace czechpmdevs\buildertools\math;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;
use function fmod;
use function max;
use function min;
use function sin;
use const M_PI;

class Math {

	public const PI_360 = M_PI * 2.0;

	/** @var float[] */
	private static array $sinTable = [];

	public static function init(): void {
		for($i = 0; $i < 65536; ++$i) {
			self::$sinTable[$i] = sin((float)$i * self::PI_360 / 65536.0);
		}
	}

	public static function sin(float $num): float {
		return self::$sinTable[(int)($num * 10430.378) & 0xffff];
	}

	public static function cos(float $num): float {
		return self::$sinTable[(int)($num * 10430.378 + 16384.0) & 0xffff];
	}

	/**
	 * Returns distance^2 between (0, 0) and (x, y)
	 *
	 * @param int|float $x
	 * @param int|float $y
	 *
	 * @return int|float
	 */
	public static function lengthSquared2d($x, $y) {
		return ($x ** 2) + ($y ** 2);
	}

	/**
	 * Returns distance^2 between (0, 0, 0) and (x, y, z)
	 *
	 * @param int|float $x
	 * @param int|float $y
	 * @param int|float $z
	 *
	 * @return int|float
	 */
	public static function lengthSquared3d($x, $y, $z) {
		return ($x ** 2) + ($y ** 2) + ($z ** 2);
	}

	/**
	 * Same as function Player->getDirection() from PocketMine-MP 3.x
	 * @link https://github.com/pmmp/PocketMine-MP/blob/92fd2d35a4e11fbd1228d5691f0897cc0914aeb1/src/pocketmine/entity/Entity.php#L1313
	 */
	public static function getPlayerDirection(Player $player): int {
		$rotation = fmod($player->getLocation()->getYaw() - 90, 360);
		if($rotation < 0) {
			$rotation += 360.0;
		}
		if((0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)) {
			return 2; //North
		} elseif(45 <= $rotation and $rotation < 135) {
			return 3; //East
		} elseif(135 <= $rotation and $rotation < 225) {
			return 0; //South
		} elseif(225 <= $rotation and $rotation < 315) {
			return 1; //West
		} else {
			return -1;
		}
	}

	public static function calculateMinAndMaxValues(Vector3 $pos1, Vector3 $pos2, bool $clampY, ?int &$minX, ?int &$maxX, ?int &$minY, ?int &$maxY, ?int &$minZ, ?int &$maxZ): void {
		$minX = (int)min($pos1->getX(), $pos2->getX());
		$maxX = (int)max($pos1->getX(), $pos2->getX());
		$minY = (int)min($pos1->getY(), $pos2->getY());
		$maxY = (int)max($pos1->getY(), $pos2->getY());
		$minZ = (int)min($pos1->getZ(), $pos2->getZ());
		$maxZ = (int)max($pos1->getZ(), $pos2->getZ());

		if(!$clampY) {
			return;
		}

		$minY = min(World::Y_MAX - 1, max(World::Y_MIN, $minY));
		$maxY = min(World::Y_MAX - 1, max(World::Y_MIN, $maxY));
	}

	public static function selectionSize(Vector3 $pos1, Vector3 $pos2): int {
		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		return (($maxX - $minX) + 1) * (($maxY - $minY) + 1) * (($maxZ - $minZ) + 1);
	}
}