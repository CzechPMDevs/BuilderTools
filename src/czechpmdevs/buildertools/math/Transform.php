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

namespace czechpmdevs\buildertools\math;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\BlockArraySizeData;
use czechpmdevs\buildertools\blockstorage\Clipboard;
use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use czechpmdevs\buildertools\utils\BlockFacingHelper;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use function atan2;
use function deg2rad;
use function round;
use function sqrt;

class Transform {
	private BlockArray $blockStorage;
	private Vector3 $relativePosition;
	private World $world;

	public function __construct(Clipboard $clipboard) {
		$this->blockStorage = $clipboard->getBlockStorage();
		$this->relativePosition = $clipboard->getRelativePosition();
		$this->world = $clipboard->getWorld();
	}

	public function rotateY(int $degrees): void {
		$rad = deg2rad($degrees);

		$diff = $this->relativePosition;
		[$diffX, $diffZ] = [$diff->getFloorX(), $diff->getFloorZ()];

		$rotationMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Y, $degrees);

		$blockArray = $this->blockStorage;
		$iterator = new BlockArrayIteratorHelper($blockArray);

		$modifiedClipboard = new BlockArray();
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);

			$dist = sqrt(Math::lengthSquared2d($x - $diffX, $z - $diffZ));
			$angle = atan2($z - $diffZ, $x - $diffX) + $rad;
			$modifiedClipboard->addBlockAt(
				(int)round($dist * Math::cos($angle)) + $diffX,
				$y,
				(int)round($dist * Math::sin($angle)) + $diffZ,
				$rotationMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$blockArray->blocks = $modifiedClipboard->blocks;
		$blockArray->coords = $modifiedClipboard->coords;
	}

	public function rotateX(int $degrees): void {
		$rad = deg2rad($degrees);

		$diff = $this->relativePosition;
		[$diffY, $diffZ] = [$diff->getFloorY(), $diff->getFloorZ()];

		$rotationMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Y, $degrees);

		$blockArray = $this->blockStorage;
		$iterator = new BlockArrayIteratorHelper($blockArray);

		$modifiedClipboard = new BlockArray();
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);

			$dist = sqrt(Math::lengthSquared2d($z - $diffZ, $y - $diffY));
			$angle = atan2($y - $diffY, $z - $diffZ) + $rad;
			$y = (int)round($dist * Math::sin($angle)) + $diffY;
			if($y < World::Y_MIN || $y >= World::Y_MAX) {
				continue;
			}

			$modifiedClipboard->addBlockAt(
				$x,
				$y,
				(int)round($dist * Math::cos($angle)) + $diffZ,
				$rotationMapping[$fullBlockId] ?? $fullBlockId);
		}

		$blockArray->blocks = $modifiedClipboard->blocks;
		$blockArray->coords = $modifiedClipboard->coords;
	}

	public function rotateZ(int $degrees): void {
		$rad = deg2rad($degrees);

		$diff = $this->relativePosition;
		[$diffX, $diffY] = [$diff->getFloorX(), $diff->getFloorY()];

		$rotationMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Z, $degrees);

		$blockArray = $this->blockStorage;
		$iterator = new BlockArrayIteratorHelper($blockArray);

		$modifiedBlockArray = new BlockArray();
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);

			$dist = sqrt(Math::lengthSquared2d($y - $diffY, $x - $diffX));
			$angle = atan2($x - $diffX, $y - $diffY) + $rad;
			$y = (int)round($dist * Math::cos($angle)) + $diffY;
			if($y < World::Y_MIN || $y >= World::Y_MAX) {
				continue;
			}

			$modifiedBlockArray->addBlockAt(
				(int)round($dist * Math::sin($angle)) + $diffX,
				$y,
				$z,
				$rotationMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$blockArray->blocks = $modifiedBlockArray->blocks;
		$blockArray->coords = $modifiedBlockArray->coords;
	}

	public function flipX(): void {
		$blockArray = $this->blockStorage;
		$sizeData = new BlockArraySizeData($blockArray);

		$flipMapping = BlockFacingHelper::getInstance()->getFlipMapping(Axis::X);

		$modifiedBlockArray = new BlockArray();
		$iterator = new BlockArrayIteratorHelper($blockArray);
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);

			$modifiedBlockArray->addBlockAt(
				($sizeData->minX + $sizeData->maxX) - $x,
				$y,
				$z,
				$flipMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$blockArray->blocks = $modifiedBlockArray->blocks;
		$blockArray->coords = $modifiedBlockArray->coords;
	}

	public function flipZ(): void {
		$blockArray = $this->blockStorage;
		$sizeData = new BlockArraySizeData($blockArray);

		$flipMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Z, 180);

		$modifiedBlockArray = new BlockArray();
		$iterator = new BlockArrayIteratorHelper($blockArray);
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);

			$modifiedBlockArray->addBlockAt(
				$x,
				$y,
				($sizeData->minZ + $sizeData->maxZ) - $z,
				$flipMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$blockArray->blocks = $modifiedBlockArray->blocks;
		$blockArray->coords = $modifiedBlockArray->coords;
	}

	public function flipY(): void {
		$blockArray = $this->blockStorage;
		$sizeData = new BlockArraySizeData($blockArray);

		$flipMapping = BlockFacingHelper::getInstance()->getFlipMapping(Axis::Y);

		$modifiedBlockArray = new BlockArray();
		$iterator = new BlockArrayIteratorHelper($blockArray);
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);
			$y = ($sizeData->minY + $sizeData->maxY) - $y;
			if($y < World::Y_MIN || $y >= World::Y_MAX) {
				continue;
			}

			$modifiedBlockArray->addBlockAt(
				$x,
				$y,
				$z,
				$flipMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$blockArray->blocks = $modifiedBlockArray->blocks;
		$blockArray->coords = $modifiedBlockArray->coords;
	}

	public function collectChanges(): Clipboard {
		return new Clipboard($this->blockStorage, $this->relativePosition, $this->world);
	}
}