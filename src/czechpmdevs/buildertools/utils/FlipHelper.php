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

// TODO - Use pre-generated data in full block ids
use pocketmine\block\BlockLegacyIds;

class FlipHelper {

	// TODO: lily pads, doors, trapdoors, terracotta, signs, buttons, tripwire hooks, banners, pillars (quartz, purpur), torches, item frames
	public static function flip(int $axis, int &$id, int &$meta): void {
		if($axis == Axis::Y_AXIS) {
			if(FlipHelper::isSlab($id)) {
				if($meta < 8) {
					$meta += 8;
				} else {
					$meta -= 8;
				}
			}
			if(FlipHelper::isStairs($id)) {
				if($meta < 4) {
					$meta += 4;
				} else {
					$meta -= 4;
				}
			}
		} elseif($axis == Axis::X_AXIS) {
			if(FlipHelper::isStairs($id)) {
				if($meta == 0) {
					$meta = 1;
				} elseif($meta == 1) {
					$meta = 0;
				} elseif($meta == 5) {
					$meta = 4;
				} elseif($meta == 4) {
					$meta = 5;
				}
			}

			if($id == BlockLegacyIds::LADDER || $id == BlockLegacyIds::FURNACE || $id == BlockLegacyIds::CHEST || $id == BlockLegacyIds::ENDER_CHEST || $id == BlockLegacyIds::TRAPPED_CHEST) {
				if($meta == 4) {
					$meta = 5;
				} elseif($meta == 5) {
					$meta = 4;
				}
			}

			if(FlipHelper::isGate($id)) {
				if($meta == 1) {
					$meta = 3;
				} elseif($meta == 3) {
					$meta = 1;
				} elseif($meta == 5) {
					$meta = 7;
				} elseif($meta == 7) {
					$meta = 5;
				}
			}

			if($id == BlockLegacyIds::TORCH || $id == BlockLegacyIds::REDSTONE_TORCH || $id == BlockLegacyIds::LEVER) {
				if($meta == 1) {
					$meta = 2;
				} elseif($meta == 2) {
					$meta = 1;
				}
			}
		} else { // Z axis
			// Stairs
			if(FlipHelper::isStairs($id)) {
				if($meta == 2) {
					$meta = 3;
				} elseif($meta == 3) {
					$meta = 2;
				} elseif($meta == 6) {
					$meta = 7;
				} elseif($meta == 7) {
					$meta = 6;
				}
			}

			if($id == BlockLegacyIds::LADDER || $id == BlockLegacyIds::FURNACE || $id == BlockLegacyIds::CHEST || $id == BlockLegacyIds::ENDER_CHEST || $id == BlockLegacyIds::TRAPPED_CHEST) {
				if($meta == 2) {
					$meta = 3;
				} elseif($meta == 3) {
					$meta = 2;
				}
			}

			// Gates
			if(FlipHelper::isGate($id)) {
				if($meta == 0) {
					$meta = 2;
				} elseif($meta == 2) {
					$meta = 0;
				} elseif($meta == 4) {
					$meta = 6;
				} elseif($meta == 6) {
					$meta = 4;
				}
			}

			if($id == BlockLegacyIds::TORCH || $id == BlockLegacyIds::REDSTONE_TORCH || $id == BlockLegacyIds::LEVER) {
				// 1 <-> 2
				if($meta == 2) {
					$meta = 1;
				} elseif($meta == 1) {
					$meta = 2;
				}
			}
		}
	}

	private static function isSlab(int $id): bool {
		return (
			$id == BlockLegacyIds::STONE_SLAB ||
			$id == BlockLegacyIds::WOODEN_SLAB ||
			$id == BlockLegacyIds::STONE_SLAB2
		);
	}

	private static function isStairs(int $id): bool {
		return (
			$id == BlockLegacyIds::WOODEN_STAIRS ||
			$id == BlockLegacyIds::COBBLESTONE_STAIRS ||
			$id == BlockLegacyIds::BRICK_STAIRS ||
			$id == BlockLegacyIds::STONE_BRICK_STAIRS ||
			$id == BlockLegacyIds::NETHER_BRICK_STAIRS ||
			$id == BlockLegacyIds::SANDSTONE_STAIRS ||
			$id == BlockLegacyIds::SPRUCE_STAIRS ||
			$id == BlockLegacyIds::BIRCH_STAIRS ||
			$id == BlockLegacyIds::JUNGLE_STAIRS ||
			$id == BlockLegacyIds::QUARTZ_STAIRS ||
			$id == BlockLegacyIds::ACACIA_STAIRS ||
			$id == BlockLegacyIds::DARK_OAK_STAIRS ||
			$id == BlockLegacyIds::RED_SANDSTONE_STAIRS ||
			$id == BlockLegacyIds::PURPUR_STAIRS
		);
	}

	private static function isGate(int $id): bool {
		return (
			$id == BlockLegacyIds::OAK_FENCE_GATE ||
			$id == BlockLegacyIds::SPRUCE_FENCE_GATE ||
			$id == BlockLegacyIds::BIRCH_FENCE_GATE ||
			$id == BlockLegacyIds::JUNGLE_FENCE_GATE ||
			$id == BlockLegacyIds::DARK_OAK_FENCE_GATE ||
			$id == BlockLegacyIds::ACACIA_FENCE_GATE
		);
	}
}