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

use InvalidArgumentException;

class SelectionSize {

	public function __construct(
		protected int $minX,
		protected int $minY,
		protected int $minZ,
		protected int $maxX,
		protected int $maxY,
		protected int $maxZ
	) {
		if($minX > $maxX || $minY > $maxY || $minZ > $maxZ) {
			throw new InvalidArgumentException("Maximum value is bigger than the minimum one.");
		}
	}

	public function expand(int $modifier): self {
		$this->minX -= $modifier;
		$this->minY -= $modifier;
		$this->minZ -= $modifier;
		$this->maxX += $modifier;
		$this->maxY += $modifier;
		$this->maxZ += $modifier;

		return $this;
	}

	public function getMinimum(int &$minX = null, int &$minY = null, int &$minZ = null): self {
		$minX = $this->minX;
		$minY = $this->minY;
		$minZ = $this->minZ;

		return $this;
	}

	public function getMaximum(int &$maxX = null, int &$maxY = null, int &$maxZ = null): self {
		$maxX = $this->maxX;
		$maxY = $this->maxY;
		$maxZ = $this->maxZ;

		return $this;
	}
}