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

use pocketmine\block\BlockIds;

// TODO - Use pre-generated data in full block ids
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
				} elseif ($meta == 4) {
					$meta = 5;
				}
			}

			if($id == BlockIds::LADDER || $id == BlockIds::FURNACE || $id == BlockIds::CHEST || $id == BlockIds::ENDER_CHEST || $id == BlockIds::TRAPPED_CHEST) {
				if($meta == 4) {
					$meta = 5;
				} elseif ($meta == 5) {
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

			if($id == BlockIds::TORCH || $id == BlockIds::REDSTONE_TORCH || $id == BlockIds::LEVER) {
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

			if($id == BlockIds::LADDER || $id == BlockIds::FURNACE  || $id == BlockIds::CHEST || $id == BlockIds::ENDER_CHEST || $id == BlockIds::TRAPPED_CHEST) {
				if($meta == 2) {
					$meta = 3;
				} elseif ($meta == 3) {
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

			if($id == BlockIds::TORCH || $id == BlockIds::REDSTONE_TORCH || $id == BlockIds::LEVER) {
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
			$id == BlockIds::STONE_SLAB ||
			$id == BlockIds::WOODEN_SLAB ||
			$id == BlockIds::STONE_SLAB2
		);
	}

	private static function isStairs(int $id): bool {
		return (
			$id == BlockIds::WOODEN_STAIRS ||
			$id == BlockIds::COBBLESTONE_STAIRS ||
			$id == BlockIds::BRICK_STAIRS ||
			$id == BlockIds::STONE_BRICK_STAIRS ||
			$id == BlockIds::NETHER_BRICK_STAIRS ||
			$id == BlockIds::SANDSTONE_STAIRS ||
			$id == BlockIds::SPRUCE_STAIRS ||
			$id == BlockIds::BIRCH_STAIRS ||
			$id == BlockIds::JUNGLE_STAIRS ||
			$id == BlockIds::QUARTZ_STAIRS ||
			$id == BlockIds::ACACIA_STAIRS ||
			$id == BlockIds::DARK_OAK_STAIRS ||
			$id == BlockIds::RED_SANDSTONE_STAIRS ||
			$id == BlockIds::PURPUR_STAIRS
		);
	}

	private static function isGate(int $id): bool {
		return (
			$id == BlockIds::OAK_FENCE_GATE ||
			$id == BlockIds::SPRUCE_FENCE_GATE ||
			$id == BlockIds::BIRCH_FENCE_GATE ||
			$id == BlockIds::JUNGLE_FENCE_GATE ||
			$id == BlockIds::DARK_OAK_FENCE_GATE ||
			$id == BlockIds::ACACIA_FENCE_GATE
		);
	}
}