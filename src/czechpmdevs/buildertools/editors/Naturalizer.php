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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\editors\object\FillSession;
use pocketmine\block\VanillaBlocks;

class Naturalizer {
	private int $air, $grass, $dirt, $stone;

	public function __construct() {
		$this->air = VanillaBlocks::AIR()->getStateId();
		$this->grass = VanillaBlocks::GRASS()->getStateId();
		$this->dirt = VanillaBlocks::DIRT()->getStateId();
		$this->stone = VanillaBlocks::STONE()->getStateId();
	}

	public function naturalize(FillSession $session, int $minX, int $maxX, int $minY, int $maxY, int $minZ, int $maxZ): void {
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$layer = 0;
				for($y = $maxY; $y >= $minY; --$y) {
					$session->getBlockAt($x, $y, $z, $fullStateId);

					if($fullStateId === $this->air) {
						$layer = 0;
						continue;
					}

					switch($layer) {
						case 0:
							$session->setBlockAt($x, $y, $z, $this->grass);
							break;
						case 1:
						case 2:
						case 3:
							$session->setBlockAt($x, $y, $z, $this->dirt);
							break;
						default:
							$session->setBlockAt($x, $y, $z, $this->stone);
					}
					++$layer;
				}
			}
		}
	}
}