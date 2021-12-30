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

namespace czechpmdevs\buildertools\shape;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use pocketmine\world\World;

class Cuboid implements Shape {
	protected BlockArray $reverseData;

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
		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveReverseData) :
			new MaskedFillSession($this->world, false, $saveReverseData, $this->mask);

		$fillSession->setDimensions($this->minX, $this->maxX, $this->minZ, $this->maxZ);
		$fillSession->loadChunks($this->world);

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
			$this->reverseData = $fillSession->getChanges()->unload();
		}

		return $this;
	}

	public function outline(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveReverseData) :
			new MaskedFillSession($this->world, false, $saveReverseData, $this->mask);

		$fillSession->setDimensions($this->minX, $this->maxX, $this->minZ, $this->maxZ);
		$fillSession->loadChunks($this->world);

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
			$this->reverseData = $fillSession->getChanges()->unload();
		}

		return $this;
	}

	public function walls(BlockIdentifierList $blockGenerator, bool $saveReverseData): self {
		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, $saveReverseData) :
			new MaskedFillSession($this->world, false, $saveReverseData, $this->mask);

		$fillSession->setDimensions($this->minX, $this->maxX, $this->minZ, $this->maxZ);
		$fillSession->loadChunks($this->world);

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
			$this->reverseData = $fillSession->getChanges()->unload();
		}

		return $this;
	}

	public function read(BlockArray $blockArray, bool $unloadReadData = true): self {
		$fillSession = $this->mask === null ?
			new FillSession($this->world, false, false) :
			new MaskedFillSession($this->world, false, false, $this->mask);

		$fillSession->setDimensions($this->minX, $this->maxX, $this->minZ, $this->maxZ);
		$fillSession->loadChunks($this->world);

		for($x = $this->minX; $x <= $this->maxX; ++$x) {
			for($z = $this->minZ; $z <= $this->maxZ; ++$z) {
				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$blockArray->addBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		if($unloadReadData) {
			$blockArray->unload();
		}

		return $this;
	}

	public function getReverseData(): BlockArray {
		return $this->reverseData;
	}
}