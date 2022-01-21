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

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\shape\Shape;
use pocketmine\math\Facing;
use pocketmine\world\World;

class ForwardExtendCopy {
	public function stack(FillSession $fillSession, Shape $shape, int $minX, int $maxX, int $minY, int $maxY, int $minZ, int $maxZ, int $count, int $direction): void {
		$temporaryBlockArray = new BlockArray();

		$shape->read($temporaryBlockArray);

		if($direction === Facing::DOWN || $direction === Facing::UP) {
			$ySize = ($maxY - $minY) + 1;
			if($direction === Facing::DOWN) {
				$ySize *= -1;
			}

			for($i = 1; $i < $count; ++$i) {
				$j = $i * $ySize;
				while($temporaryBlockArray->hasNext()) {
					$temporaryBlockArray->readNext($x, $y, $z, $fullBlockId);
					if($y >= World::Y_MIN && $y < World::Y_MAX) {
						$fillSession->setBlockAt($x, $y + $j, $z, $fullBlockId);
					}
				}

				$temporaryBlockArray->offset = 0;
			}
		} elseif($direction === Facing::NORTH || $direction === Facing::SOUTH) {
			$xSize = ($maxX - $minX) + 1;

			if($direction === Facing::SOUTH) {
				$fillSession->setDimensions($minX, $minX + ($xSize * $count), $minZ, $maxZ);
			} else {
				$fillSession->setDimensions($minX - ($xSize * $count), $minX, $minZ, $maxZ);
				$xSize *= -1;
			}

			for($i = 1; $i < $count; ++$i) {
				$j = $i * $xSize;
				while($temporaryBlockArray->hasNext()) {
					$temporaryBlockArray->readNext($x, $y, $z, $fullBlockId);
					$fillSession->setBlockAt($x + $j, $y, $z, $fullBlockId);
				}

				// Resets the array reader
				$temporaryBlockArray->offset = 0;
			}
		} else {
			$zSize = ($maxZ - $minZ) + 1;

			if($direction === Facing::WEST) {
				$fillSession->setDimensions($minX, $maxX, $minZ, $minZ + ($zSize * $count));
			} else {
				$fillSession->setDimensions($minX, $maxX, $minZ - ($zSize * $count), $maxZ);
				$zSize = -$zSize;
			}

			for($i = 1; $i < $count; ++$i) {
				$j = $i * $zSize;
				while($temporaryBlockArray->hasNext()) {
					$temporaryBlockArray->readNext($x, $y, $z, $fullBlockId);
					$fillSession->setBlockAt($x, $y, $z + $j, $fullBlockId);
				}

				// Resets array reader
				$temporaryBlockArray->offset = 0;
			}
		}

		unset($temporaryBlockArray);
	}
}