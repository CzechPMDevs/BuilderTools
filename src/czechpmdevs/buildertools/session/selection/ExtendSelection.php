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

namespace czechpmdevs\buildertools\session\selection;

use czechpmdevs\buildertools\math\Math;
use pocketmine\world\Position;
use RuntimeException;
use function max;
use function min;

class ExtendSelection extends CuboidSelection {
	public function handleWandAxeBlockBreak(Position $position): void {
		$this->firstPosition = $this->secondPosition = $position;
		$this->world = $position->getWorld();
	}

	public function handleWandAxeBlockClick(Position $position): void {
		if(!isset($this->firstPosition)) {
			throw new RuntimeException("First position has to be selected first");
		}

		if($this->world->getId() !== $position->getWorld()->getId()) {
			throw new RuntimeException("Selected positions are not in the same world");
		}

		if(!isset($this->secondPosition)) {
			$this->secondPosition = $position;
			return;
		}

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		[$x, $y, $z] = [$position->getFloorX(), $position->getFloorY(), $position->getFloorZ()];

		$this->firstPosition = new Position(min($x, $minX), min($y, $minY), min($z, $minZ), $position->getWorld());
		$this->secondPosition = new Position(max($x, $maxX), max($y, $maxY), max($z, $maxZ), $position->getWorld());
	}
}