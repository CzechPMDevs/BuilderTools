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

use pocketmine\block\BlockLegacyIds;
use function in_array;

class RotationHelper {

	private const STAIRS_IDS = [BlockLegacyIds::COBBLESTONE_STAIRS, BlockLegacyIds::WOODEN_STAIRS, BlockLegacyIds::SPRUCE_STAIRS, BlockLegacyIds::BIRCH_STAIRS, BlockLegacyIds::JUNGLE_STAIRS, BlockLegacyIds::ACACIA_STAIRS, BlockLegacyIds::STONE_BRICK_STAIRS, BlockLegacyIds::SANDSTONE_STAIRS, BlockLegacyIds::RED_SANDSTONE_STAIRS, BlockLegacyIds::BRICK_STAIRS, BlockLegacyIds::NETHER_BRICK_STAIRS, BlockLegacyIds::QUARTZ_STAIRS, BlockLegacyIds::PURPUR_STAIRS];
	private const STAIRS_ROTATION_DATA = [0 => 2, 1 => 3, 2 => 1, 3 => 0, 4 => 6, 5 => 7, 6 => 5, 7 => 4];

	/** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
	public static function rotate(int $degrees, int &$id, int &$meta): void {
		if($degrees == 90) {
			RotationHelper::rotate90($id, $meta);
		} elseif($degrees == 180) {
			RotationHelper::rotate180($id, $meta);
		} elseif($degrees == 270) {
			RotationHelper::rotate270($id, $meta);
		}
	}

	public static function rotate90(int $id, int &$meta): void {
		if(in_array($id, RotationHelper::STAIRS_IDS, true)) {
			$meta = RotationHelper::STAIRS_ROTATION_DATA[$meta % 8];
		}
	}

	public static function rotate180(int $id, int &$meta): void {
		if(in_array($id, RotationHelper::STAIRS_IDS, true)) {
			$meta = RotationHelper::STAIRS_ROTATION_DATA[$meta % 8];
			$meta = RotationHelper::STAIRS_ROTATION_DATA[$meta % 8];
		}
	}

	public static function rotate270(int $id, int &$meta): void {
		if(in_array($id, RotationHelper::STAIRS_IDS, true)) {
			$meta = RotationHelper::STAIRS_ROTATION_DATA[$meta % 8];
			$meta = RotationHelper::STAIRS_ROTATION_DATA[$meta % 8];
			$meta = RotationHelper::STAIRS_ROTATION_DATA[$meta % 8];
		}
	}
}