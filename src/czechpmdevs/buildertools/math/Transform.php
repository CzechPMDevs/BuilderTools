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

namespace czechpmdevs\buildertools\math;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\SelectionData;
use czechpmdevs\buildertools\utils\BlockFacingHelper;
use pocketmine\math\Axis;
use pocketmine\world\World;
use function atan2;
use function deg2rad;
use function round;
use function sqrt;

class Transform {

	public function __construct(
		private SelectionData $clipboard
	) {
		$this->clipboard->load();
	}

	public function rotateY(int $degrees): void {
		$rad = deg2rad($degrees);

		$diff = $this->clipboard->getPlayerPosition();
		[$diffX, $diffZ] = [$diff->getFloorX(), $diff->getFloorZ()];

		$rotationMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Y, $degrees);

		$modifiedClipboard = new BlockArray();
		while($this->clipboard->hasNext()) {
			$this->clipboard->readNext($x, $y, $z, $fullBlockId);

			$dist = sqrt(Math::lengthSquared2d($x - $diffX, $z - $diffZ));
			$angle = atan2($z - $diffZ, $x - $diffX) + $rad;
			$modifiedClipboard->addBlockAt(
				(int)round($dist * Math::cos($angle)) + $diffX,
				$y,
				(int)round($dist * Math::sin($angle)) + $diffZ,
				$rotationMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$this->clipboard->blocks = $modifiedClipboard->blocks;
		$this->clipboard->coords = $modifiedClipboard->coords;
		$this->clipboard->offset = 0;
	}

	public function rotateX(int $degrees): void {
		$rad = deg2rad($degrees);

		$diff = $this->clipboard->getPlayerPosition();
		[$diffY, $diffZ] = [$diff->getFloorY(), $diff->getFloorZ()];

		$rotationMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Y, $degrees);

		$modifiedClipboard = new BlockArray();
		while($this->clipboard->hasNext()) {
			$this->clipboard->readNext($x, $y, $z, $fullBlockId);

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

		$this->clipboard->blocks = $modifiedClipboard->blocks;
		$this->clipboard->coords = $modifiedClipboard->coords;
		$this->clipboard->offset = 0;
	}

	public function rotateZ(int $degrees): void {
		$rad = deg2rad($degrees);

		$diff = $this->clipboard->getPlayerPosition();
		[$diffX, $diffY] = [$diff->getFloorX(), $diff->getFloorY()];

		$rotationMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Z, $degrees);

		$modifiedBlockArray = new BlockArray();
		while($modifiedBlockArray->hasNext()) {
			$this->clipboard->readNext($x, $y, $z, $fullBlockId);

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

		$this->clipboard->blocks = $modifiedBlockArray->blocks;
		$this->clipboard->coords = $modifiedBlockArray->coords;
		$this->clipboard->offset = 0;
	}

	public function flipX(): void {
		$sizeData = $this->clipboard->getSizeData();
		$flipMapping = BlockFacingHelper::getInstance()->getFlipMapping(Axis::X);

		$modifiedBlockArray = new BlockArray();
		while($this->clipboard->hasNext()) {
			$this->clipboard->readNext($x, $y, $z, $fullBlockId);

			$modifiedBlockArray->addBlockAt(
				($sizeData->minX + $sizeData->maxX) - $x,
				$y,
				$z,
				$flipMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$this->clipboard->blocks = $modifiedBlockArray->blocks;
		$this->clipboard->coords = $modifiedBlockArray->coords;
		$this->clipboard->offset = 0;
	}

	public function flipZ(): void {
		$sizeData = $this->clipboard->getSizeData();
		$flipMapping = BlockFacingHelper::getInstance()->getRotationMapping(Axis::Z, 180);

		$modifiedBlockArray = new BlockArray();
		while($this->clipboard->hasNext()) {
			$this->clipboard->readNext($x, $y, $z, $fullBlockId);

			$modifiedBlockArray->addBlockAt(
				$x,
				$y,
				($sizeData->minZ + $sizeData->maxZ) - $z,
				$flipMapping[$fullBlockId] ?? $fullBlockId
			);
		}

		$this->clipboard->blocks = $modifiedBlockArray->blocks;
		$this->clipboard->coords = $modifiedBlockArray->coords;
		$this->clipboard->offset = 0;
	}

	public function flipY(): void {
		$sizeData = $this->clipboard->getSizeData();
		$flipMapping = BlockFacingHelper::getInstance()->getFlipMapping(Axis::Y);

		$modifiedBlockArray = new BlockArray();
		while($this->clipboard->hasNext()) {
			$this->clipboard->readNext($x, $y, $z, $fullBlockId);
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

		$this->clipboard->blocks = $modifiedBlockArray->blocks;
		$this->clipboard->coords = $modifiedBlockArray->coords;
		$this->clipboard->offset = 0;
	}

	public function close(): void {
		$this->clipboard->save();
	}
}