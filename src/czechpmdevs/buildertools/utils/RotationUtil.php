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

namespace czechpmdevs\buildertools\utils;

use czechpmdevs\buildertools\blockstorage\SelectionData;
use czechpmdevs\buildertools\math\Math;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function atan2;
use function deg2rad;
use function fmod;
use function in_array;
use function round;
use function sqrt;

class RotationUtil {

	public const VALID_DEGREES = [RotationUtil::ROTATE_0, RotationUtil::ROTATE_90, RotationUtil::ROTATE_180, RotationUtil::ROTATE_270];

	public const ROTATE_0 = 0;
	public const ROTATE_90 = 90;
	public const ROTATE_180 = 180;
	public const ROTATE_270 = 270;
	public const ROTATE_360 = 0;

	public static function rotate(SelectionData $blockArray, int $axis, int $degrees): SelectionData {
		if($degrees == 0) {
			return $blockArray;
		}

		$deg = $degrees % 360;
		$rad = deg2rad($degrees % 360);

		$diff = $blockArray->getPlayerPosition();

		$modifiedBlockArray = new SelectionData();
		switch($axis) {
			case Axis::Y:
				while($blockArray->hasNext()) {
					$blockArray->readNext($x, $y, $z, $fullBlockId);
					RotationHelper::rotate($deg, $fullBlockId);

					$dist = sqrt(Math::lengthSquared2d($x - $diff->getX(), $z - $diff->getZ()));
					$alfa = atan2($z - $diff->getZ(), $x - $diff->getX()) + $rad;
					$modifiedBlockArray->addBlock(new Vector3((int)round($dist * Math::cos($alfa)) + $diff->getX(), $y, (int)round($dist * Math::sin($alfa)) + $diff->getZ()), $fullBlockId);
				}

				$blockArray->blocks = $modifiedBlockArray->blocks;
				$blockArray->coords = $modifiedBlockArray->coords;
				$blockArray->offset = 0;
				return $blockArray;
			case Axis::X:
				while($blockArray->hasNext()) {
					$blockArray->readNext($x, $y, $z, $fullBlockId);

					$dist = sqrt(Math::lengthSquared2d($y - $diff->getY(), $z - $diff->getZ()));
					$alfa = atan2($y - $diff->getY(), $z - $diff->getZ()) + $rad;
					$y = (int)round($dist * Math::cos($alfa)) + $diff->getX();
					if($y < World::Y_MIN || $y >= World::Y_MAX) {
						continue;
					}

					$modifiedBlockArray->addBlock(new Vector3($x, $y, (int)round($dist * Math::sin($alfa)) + $diff->getZ()), $fullBlockId);
				}

				$blockArray->coords = $modifiedBlockArray->coords;
				$blockArray->offset = 0;
				return $blockArray;
			case Axis::Z:
				while($blockArray->hasNext()) {
					$blockArray->readNext($x, $y, $z, $fullBlockId);

					$dist = sqrt(Math::lengthSquared2d($x - $diff->getX(), $y - $diff->getY()));
					$alfa = atan2($x - $diff->getX(), $y - $diff->getY()) + $rad;
					$y = (int)round($dist * Math::sin($alfa));
					if($y < World::Y_MIN || $y >= World::Y_MAX) {
						continue;
					}
					$modifiedBlockArray->addBlock(new Vector3((int)round($dist * Math::cos($alfa)), $y, $z), $fullBlockId);
				}

				$blockArray->coords = $modifiedBlockArray->coords;
				$blockArray->offset = 0;
				return $blockArray;
			default:
				return $blockArray;
		}
	}

	public static function isDegreeValueValid(int $degrees): bool {
		$degrees = (int)fmod($degrees, 360);

		return in_array($degrees, RotationUtil::VALID_DEGREES, true);
	}

	public static function getRotation(int $degrees): int {
		return match ((int)fmod($degrees, 360)) {
			0 => RotationUtil::ROTATE_0,
			90 => RotationUtil::ROTATE_90,
			180 => RotationUtil::ROTATE_180,
			270 => RotationUtil::ROTATE_270,
			default => RotationUtil::ROTATE_360,
		};
	}
}