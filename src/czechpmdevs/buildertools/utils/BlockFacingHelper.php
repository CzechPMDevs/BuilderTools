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

namespace czechpmdevs\buildertools\utils;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;
use function array_key_exists;
use function count;
use function intdiv;
use function method_exists;

class BlockFacingHelper {
	use SingletonTrait;

	private bool $isRotationMappingLoaded = false;

	/**
	 * @var int[][]
	 * @phpstan-var array<1|2|3, array<int, int>>
	 */
	private array $rotateXMap = [];

	/**
	 * @var int[][]
	 * @phpstan-var array<1|2|3, array<int, int>>
	 */
	private array $rotateYMap = [];

	/**
	 * @var int[][]
	 * @phpstan-var array<1|2|3, array<int, int>>
	 */
	private array $rotateZMap = [];

	private bool $isFlipMappingLoaded = false;

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $flipXMap = [];

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $flipYMap = [];

	/**
	 * @var int[]
	 * @phpstan-var array<int, int>
	 */
	private array $flipZMap = [];

	private function hasFacingTrait(Block $block): bool {
		if(!method_exists($block, "getFacing")) {
			return false;
		}

		try {
			$ref = new ReflectionClass($block);

			$getter = $ref->getMethod("getFacing");
			if($getter->getReturnType()?->__toString() !== "int") {
				return false;
			}
			if(count($getter->getParameters()) !== 0) {
				return false;
			}

			$setter = $ref->getMethod("setFacing");
			if($setter->getReturnType()?->__toString() !== "self") {
				return false;
			}

			$setterParams = $setter->getParameters();
			if(!array_key_exists(0, $setterParams) || $setterParams[0]->getType()?->__toString() !== "int") {
				return false;
			}
		} catch(ReflectionException) {
			return false;
		}

		return true;
	}

	private function lazyLoadRotationMapping(): void {
		if($this->isRotationMappingLoaded) {
			return;
		}

		foreach(BlockFactory::getInstance()->getAllKnownStates() as $block) {
			if($this->hasFacingTrait($block)) {
				$originalFullId = $block->getFullId();

				$x = clone $block;
				$y = clone $block;
				$z = $block;

				for($i = 1; $i <= 3; ++$i) {
					try {
						$x->setFacing(Facing::rotateX($x->getFacing(), true)); // @phpstan-ignore-line
						$this->rotateXMap[$i][$originalFullId] = $x->getFullId();
					} catch(InvalidArgumentException) {
					}

					try {
						$y->setFacing(Facing::rotateY($y->getFacing(), true)); // @phpstan-ignore-line
						$this->rotateYMap[$i][$originalFullId] = $y->getFullId();
					} catch(InvalidArgumentException) {
					}

					try {
						$z->setFacing(Facing::rotateZ($z->getFacing(), true)); // @phpstan-ignore-line
						$this->rotateZMap[$i][$originalFullId] = $z->getFullId();
					} catch(InvalidArgumentException) {
					}
				}
				continue;
			}
		}

		$this->isRotationMappingLoaded = true;
	}

	private function lazyLoadFlipMapping(): void {
		if($this->isFlipMappingLoaded) {
			return;
		}

		foreach(BlockFactory::getInstance()->getAllKnownStates() as $block) {
			if($this->hasFacingTrait($block)) {
				$sourceId = $block->getFullId();
				$sourceFacing = $block->getFacing(); // @phpstan-ignore-line
				if(Facing::axis($sourceFacing) === Axis::X) {
					try {
						$block->setFacing(Facing::opposite($sourceFacing)); // @phpstan-ignore-line
					} catch(InvalidArgumentException) {
					}

					$this->flipXMap[$sourceId] = $block->getFullId();
				}
				if(Facing::axis($sourceFacing) === Axis::Z) {
					try {
						$block->setFacing(Facing::opposite($sourceFacing)); // @phpstan-ignore-line
					} catch(InvalidArgumentException) {
					}

					$this->flipZMap[$sourceId] = $block->getFullId();
				}
				if(Facing::axis($sourceFacing) === Axis::Y) {
					try {
						$block->setFacing(Facing::opposite($sourceFacing)); // @phpstan-ignore-line
					} catch(InvalidArgumentException) {
					}

					$this->flipYMap[$sourceId] = $block->getFullId();
				}
			}
		}

		$this->isFlipMappingLoaded = true;
	}

	/**
	 * @return array<int, int>
	 */
	public function getRotationMapping(int $axis, int $degrees): array {
		$this->lazyLoadRotationMapping();
		if($axis === Axis::X) {
			return $this->rotateXMap[intdiv($degrees, 90)] ?? [];
		} elseif($axis === Axis::Y) {
			return $this->rotateYMap[intdiv($degrees, 90)] ?? [];
		} else {
			return $this->rotateZMap[intdiv($degrees, 90)] ?? [];
		}
	}

	/**
	 * @return array<int, int>
	 */
	public function getFlipMapping(int $axis): array {
		$this->lazyLoadFlipMapping();
		if($axis === Axis::X) {
			return $this->flipXMap;
		} elseif($axis === Axis::Y) {
			return $this->flipYMap;
		} else {
			return $this->flipZMap;
		}
	}
}