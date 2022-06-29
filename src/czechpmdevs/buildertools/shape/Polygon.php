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
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\math\IntVector2;
use czechpmdevs\buildertools\math\Math;
use pocketmine\world\World;
use function count;
use function str_repeat;

class Polygon implements Shape {
	protected BlockArray $reverseData;

	public function __construct(
		protected World $world,
		protected int $minY,
		protected int $maxY,
		/** @var IntVector2[] */
		public array $points,
		protected ?BlockIdentifierList $mask = null
	) {}

	public function fill(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		Math::calculateMultipleMinAndMaxValues($minX, $maxX, $minZ, $maxZ, ...$this->points);

		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveReverseData) :
			new MaskedFillSession($this->world, false, $saveReverseData, $this->mask);

		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($this->world);

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if(!$this->contains($x, $z)) {
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
			$this->reverseData = $fillSession->getChanges();
		}

		return $this;
	}

	public function outline(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		Math::calculateMultipleMinAndMaxValues($minX, $maxX, $minZ, $maxZ, ...$this->points);

		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveReverseData) :
			new MaskedFillSession($this->world, false, $saveReverseData, $this->mask);

		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($this->world);


		$cacheOffsetX = $minX - 1;
		$cacheOffsetZ = $minZ - 1;

		$cacheSizeX = $maxX - $cacheOffsetX + 2;
		$cacheSizeZ = $maxZ - $cacheOffsetZ + 2;

		$cache = str_repeat("0", $cacheSizeX * $cacheSizeZ);
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if(!$this->contains($x, $z)) {
					continue;
				}

				$cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ) * $cacheSizeX] = "1";
			}
		}

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if($cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ) * $cacheSizeX] === "0") {
					continue;
				}

				$draw = false;
				if($cache[($x - $cacheOffsetX + 1) + ($z - $cacheOffsetZ) * $cacheSizeX] === "0") {
					$draw = true;
				} elseif($cache[($x - $cacheOffsetX - 1) + ($z - $cacheOffsetZ) * $cacheSizeX] === "0") {
					$draw = true;
				} elseif($cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ + 1) * $cacheSizeX] === "0") {
					$draw = true;
				} elseif($cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ - 1) * $cacheSizeX] === "0") {
					$draw = true;
				}

				if(!$draw) {
					$blockGenerator->nextBlock($fullBlockId);
					$fillSession->setBlockAt($x, $this->minY, $z, $fullBlockId);
					if($this->maxY === $this->minY) {
						continue;
					}

					$blockGenerator->nextBlock($fullBlockId);
					$fillSession->setBlockAt($x, $this->maxY, $z, $fullBlockId);
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
			$this->reverseData = $fillSession->getChanges();
		}

		return $this;
	}

	public function walls(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		Math::calculateMultipleMinAndMaxValues($minX, $maxX, $minZ, $maxZ, ...$this->points);

		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveReverseData) :
			new MaskedFillSession($this->world, false, $saveReverseData, $this->mask);

		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($this->world);


		$cacheOffsetX = $minX - 1;
		$cacheOffsetZ = $minZ - 1;

		$cacheSizeX = $maxX - $cacheOffsetX + 2;
		$cacheSizeZ = $maxZ - $cacheOffsetZ + 2;

		$cache = str_repeat("0", $cacheSizeX * $cacheSizeZ);
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if(!$this->contains($x, $z)) {
					continue;
				}

				$cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ) * $cacheSizeX] = "1";
			}
		}

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if($cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ) * $cacheSizeX] === "0") {
					continue;
				}

				$draw = false;
				if($cache[($x - $cacheOffsetX + 1) + ($z - $cacheOffsetZ) * $cacheSizeX] === "0") {
					$draw = true;
				} elseif($cache[($x - $cacheOffsetX - 1) + ($z - $cacheOffsetZ) * $cacheSizeX] === "0") {
					$draw = true;
				} elseif($cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ + 1) * $cacheSizeX] === "0") {
					$draw = true;
				} elseif($cache[($x - $cacheOffsetX) + ($z - $cacheOffsetZ - 1) * $cacheSizeX] === "0") {
					$draw = true;
				}

				if(!$draw) {
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
			$this->reverseData = $fillSession->getChanges();
		}

		return $this;
	}

	public function read(BlockArray $blockArray, bool $unloadReadData = true): self {
		Math::calculateMultipleMinAndMaxValues($minX, $maxX, $minZ, $maxZ, ...$this->points);

		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, false) :
			new MaskedFillSession($this->world, false, false, $this->mask);

		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($this->world);

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				if(!$this->contains($x, $z)) {
					continue;
				}
				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$blockArray->addBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		return $this;
	}

	private function contains(mixed $targetX, mixed $targetZ): bool {
		$inside = false;
		$nPoints = count($this->points);

		$oldX = $this->points[$nPoints - 1]->x;
		$oldY = $this->points[$nPoints - 1]->y;
		for($i = 0; $i < $nPoints; ++$i) {

			$newX = $this->points[$i]->x;
			$newY = $this->points[$i]->y;

			if($newX === $targetX && $newY === $targetZ) {
				return true;
			}

			if($newX > $oldX) {
				$x1 = $oldX;
				$x2 = $newX;
				$z1 = $oldY;
				$z2 = $newY;
			} else {
				$x1 = $newX;
				$x2 = $oldX;
				$z1 = $newY;
				$z2 = $oldY;
			}

			if($x1 <= $targetX && $targetX <= $x2) {
				$crossProduct = ($targetZ - $z1) * ($x2 - $x1) - ($z2 - $z1) * ($targetX - $x1);
				if($crossProduct === 0) {
					if(($z1 <= $targetZ) === ($targetZ <= $z2)) {
						return true;
					}
				} elseif($crossProduct < 0 && ($x1 !== $targetX)) {
					$inside = !$inside;
				}
			}
			$oldX = $newX;
			$oldY = $newY;
		}

		return $inside;
	}

	public function getReverseData(): BlockArray {
		return $this->reverseData;
	}
}