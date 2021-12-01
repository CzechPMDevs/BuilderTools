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

namespace czechpmdevs\buildertools\world;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use pocketmine\math\AxisAlignedBB;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class TileExplorer {
	use SingletonTrait;

	public function saveTiles(int $minX, int $maxX, int $minY, int $maxY, int $minZ, int $maxZ, World $world, BlockArray $target): void {
		[$minChunkX, $maxChunkX, $minChunkZ, $maxChunkZ] = [$minX >> 4, $maxX >> 4, $minZ >> 4, $maxZ >> 4];
		$bb = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
		for($chunkX = $minChunkX; $chunkX <= $maxChunkX; ++$chunkX) {
			for($chunkZ = $minChunkZ; $chunkZ <= $maxChunkZ; ++$chunkZ) {
				foreach($world->getChunk($chunkX, $chunkZ)->getTiles() as $tile) {
					if($bb->isVectorInside($tile->getPosition())) {
						$nbt = $tile->getCleanedNBT();
						if($nbt === null) {
							continue;
						}

						$target->getTiles()->addTile($tile->getPosition(), $nbt);
					}
				}
			}
		}
	}
}