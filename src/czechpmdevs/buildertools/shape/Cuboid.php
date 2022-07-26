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

namespace czechpmdevs\buildertools\shape;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\blockstorage\TileArray;
use czechpmdevs\buildertools\world\FillSession;
use czechpmdevs\buildertools\world\MaskedFillSession;
use pocketmine\world\World;

class Cuboid implements Shape {
	protected BlockStorageHolder $reverseData;

	public function __construct(
		protected World $world,
		protected int $minX,
		protected int $maxX,
		protected int $minY,
		protected int $maxY,
		protected int $minZ,
		protected int $maxZ,
		protected ?BlockIdentifierList $mask = null
	) {}

	public function fill(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		$fillSession = $this->loadFillSession($saveReverseData);

		for($x = $this->minX; $x <= $this->maxX; ++$x) {
			for($z = $this->minZ; $z <= $this->maxZ; ++$z) {
				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					$blockGenerator->nextBlock($fullBlockId);
					$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		$fillSession->reloadChunks($this->world);
		$fillSession->close();

		if($saveReverseData) {
			$this->reverseData = new BlockStorageHolder($fillSession->getBlockChanges(), $fillSession->getTileChanges(), $this->world);
		}

		return $this;
	}

	public function outline(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		$fillSession = $this->loadFillSession($saveReverseData);

		for($x = $this->minX; $x <= $this->maxX; ++$x) {
			$skipX = $x !== $this->minX && $x !== $this->maxX;
			for($z = $this->minZ; $z <= $this->maxZ; ++$z) {
				$skipZ = $z !== $this->minZ && $z !== $this->maxZ;
				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					if($skipX && $skipZ && $y !== $this->minY && $y !== $this->maxY) {
						continue;
					}

					$blockGenerator->nextBlock($fullBlockId);
					$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		$fillSession->reloadChunks($this->world);
		$fillSession->close();

		if($saveReverseData) {
			$this->reverseData = new BlockStorageHolder($fillSession->getBlockChanges(), $fillSession->getTileChanges(), $this->world);
		}

		return $this;
	}

	public function walls(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		$fillSession = $this->loadFillSession($saveReverseData);

		for($x = $this->minX; $x <= $this->maxX; ++$x) {
			$skipX = $x !== $this->minX && $x !== $this->maxX;
			for($z = $this->minZ; $z <= $this->maxZ; ++$z) {
				if($skipX && $z !== $this->minZ && $z !== $this->maxZ) {
					continue;
				}

				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					$blockGenerator->nextBlock($fullBlockId);
					$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		$fillSession->reloadChunks($this->world);
		$fillSession->close();

		if($saveReverseData) {
			$this->reverseData = new BlockStorageHolder($fillSession->getBlockChanges(), $fillSession->getTileChanges(), $this->world);
		}

		return $this;
	}

	public function read(BlockArray $blockArray, TileArray $tileArray): self {
		$fillSession = $this->loadFillSession();

		for($x = $this->minX; $x <= $this->maxX; ++$x) {
			for($z = $this->minZ; $z <= $this->maxZ; ++$z) {
				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$blockArray->addBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		for($x = $this->minX >> 4, $maxX = $this->maxX >> 4; $x <= $maxX; ++$x) {
			for($z = $this->maxZ >> 4, $maxZ = $this->maxZ >> 4; $z <= $maxZ; ++$z) {
				$chunk = $this->world->getChunk($x, $z);
				if($chunk === null) {
					continue;
				}

				foreach($chunk->getTiles() as $tile) {
					/** @var int $x */
					$x = $tile->getPosition()->getX();
					/** @var int $y */
					$y = $tile->getPosition()->getY();
					/** @var int $z */
					$z = $tile->getPosition()->getZ();

					if(
						$x >= $this->minX && $x <= $this->maxX &&
						$y >= $this->minY && $y <= $this->maxY &&
						$z >= $this->minZ && $z <= $this->maxZ
					) {
						$tileArray->addTileAt($x, $y, $z, $tile->saveNBT());
					}
				}
			}
		}

		return $this;
	}

	protected function clearTiles(): self {
		for($chunkX = $this->minX >> 4, $maxX = $this->maxX >> 4; $chunkX <= $maxX; ++$chunkX) {
			for($chunkZ = $this->maxZ >> 4, $maxZ = $this->maxZ >> 4; $chunkZ <= $maxZ; ++$chunkZ) {
				$chunk = $this->world->getChunk($chunkX, $chunkZ);
				if($chunk === null) {
					continue;
				}

				foreach($chunk->getTiles() as $tile) {
					/** @var int $x */
					$x = $tile->getPosition()->getX();
					/** @var int $y */
					$y = $tile->getPosition()->getY();
					/** @var int $z */
					$z = $tile->getPosition()->getZ();

					if(
						$x >= $this->minX && $x <= $this->maxX &&
						$y >= $this->minY && $y <= $this->maxY &&
						$z >= $this->minZ && $z <= $this->maxZ
					) {
						$tile->close();
					}
				}
			}
		}
		return $this;
	}

	public function getReverseData(): BlockStorageHolder {
		return $this->reverseData;
	}

	public function loadFillSession(bool $saveBlockChanges = false, bool $saveTileChanges = false): FillSession|MaskedFillSession {
		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveBlockChanges, $saveTileChanges) :
			new MaskedFillSession($this->world, false, $saveBlockChanges, $saveTileChanges, $this->mask);

		$fillSession->setDimensions($this->minX, $this->maxX, $this->minZ, $this->maxZ);
		$fillSession->loadChunks($this->world);

		return $fillSession;
	}
}