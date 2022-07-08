<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
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

use pocketmine\block\BlockFactory;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use function array_key_exists;

class BlockToTileMap {
	use SingletonTrait;

	/** @var array<int, class-string<Tile>> */
	protected array $blockToTileMap;

	public static function make(): self {
		$instance = new self;
		foreach(BlockFactory::getInstance()->getAllKnownStates() as $block) {
			if(($tile = $block->getIdInfo()->getTileClass()) !== null) {
				$instance->blockToTileMap[$block->getIdInfo()->getBlockId()] = $tile;
			}
		}

		return $instance;
	}

	public function createTile(World $world, int $x, int $y, int $z, int $blockId): ?Tile {
		if(!array_key_exists($blockId, $this->blockToTileMap)) {
			return null;
		}

		return new ($this->blockToTileMap[$blockId])($world, new Vector3($x, $y, $z));
	}
}