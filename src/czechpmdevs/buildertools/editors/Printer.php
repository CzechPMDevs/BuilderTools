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
use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\blockstorage\helpers\DuplicateBlockCleanHelper;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\math\BlockGenerator;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\session\SessionManager;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use pocketmine\world\World;
use function abs;

/** @deprecated */
class Printer {
	use SingletonTrait;

	public const CUBE = 0x00;
	public const SPHERE = 0x01;
	public const CYLINDER = 0x02;
	public const HOLLOW_CUBE = 0x03;
	public const HOLLOW_SPHERE = 0x04;
	public const HOLLOW_CYLINDER = 0x05;

	public function draw(Player $player, Position $center, Block $block, int $brush = 4, int $mode = 0x00, bool $throwBlock = false): void {
		$updates = new BlockArray();
		$center = Position::fromObject($center->floor(), $center->getWorld());

		$level = $center->getWorld();

		$placeBlock = function(Vector3 $vector3) use ($level, $updates, $block, $center, $throwBlock) {
			if($throwBlock) {
				$vector3 = $this->throwBlock(Position::fromObject($vector3, $center->getWorld()));
			}
			if($vector3->getY() < 0) {
				return;
			}

			$updates->addBlock($vector3, $level->getBlock($vector3, true, false)->getId());

			/** @phpstan-ignore-next-line */
			$level->setBlockAt($vector3->getX(), $vector3->getY(), $vector3->getZ(), $block); // We provide valid values
		};

		if($mode === Printer::CUBE) {
			foreach(BlockGenerator::generateCube($brush) as [$x, $y, $z]) {
				$placeBlock($center->add($x, $y, $z));
			}
		} elseif($mode === Printer::SPHERE) {
			foreach(BlockGenerator::generateSphere($brush) as [$x, $y, $z]) {
				$placeBlock($center->add($x, $y, $z));
			}
			(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);
		} elseif($mode === Printer::CYLINDER) {
			foreach(BlockGenerator::generateCylinder($brush, $brush) as [$x, $y, $z]) {
				$placeBlock($center->add($x, $y, $z));
			}
			(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);
		} elseif($mode === Printer::HOLLOW_CUBE) {
			foreach(BlockGenerator::generateCube($brush, true) as [$x, $y, $z]) {
				$placeBlock($center->add($x, $y, $z));
			}
		} elseif($mode === Printer::HOLLOW_SPHERE) {
			foreach(BlockGenerator::generateSphere($brush, true) as [$x, $y, $z]) {
				$placeBlock($center->add($x, $y, $z));
			}
			(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);
		} elseif($mode === Printer::HOLLOW_CYLINDER) {
			foreach(BlockGenerator::generateCylinder($brush, $brush, true) as [$x, $y, $z]) {
				$placeBlock($center->add($x, $y, $z));
			}
			(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);
		}

		(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($updates, $player->getWorld()));
	}

	private function throwBlock(Position $position): Vector3 {
		$level = $position->getWorld();

		$x = $position->getFloorX();
		$y = $position->getFloorY();
		$z = $position->getFloorZ();

		/** @noinspection PhpStatementHasEmptyBodyInspection */
		for(; $y >= 0 && $level->getBlockAt($x, $y, $z, true, false)->getId() === 0; $y--) ;

		return new Vector3($x, ++$y, $z);
	}

	public function makeSphere(Player $player, Position $center, int $radius, string $blockArgs, bool $hollow = false): UpdateResult {
		$timer = new Timer();
		$center = Position::fromObject($center->floor(), $center->getWorld());
		$radius = abs($radius);
		if($radius === 0) {
			return UpdateResult::error("Radius could not be 0");
		}

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());
		if(!$stringToBlockDecoder->isValid()) {
			return UpdateResult::error("No blocks found in string $blockArgs");
		}

		$floorX = $center->getFloorX();
		$floorY = $center->getFloorY();
		$floorZ = $center->getFloorZ();

		$fillSession = new FillSession($player->getWorld(), false, true);
		$fillSession->setDimensions($floorX - $radius, $floorX + $radius, $floorZ - $radius, $floorZ + $radius);

		$incDivX = 0;
		for($x = 0; $x <= $radius; ++$x) {
			$divX = $incDivX; // divX = dividedX = x / radius
			$incDivX = ($x + 1) / $radius; // incDivX = increasedDividedX = (x + 1) / radius

			$incDivY = 0;
			for($y = 0; $y <= $radius; ++$y) {
				$divY = $incDivY;
				$incDivY = ($y + 1) / $radius;

				$incDivZ = 0;
				for($z = 0; $z <= $radius; ++$z) {
					$divZ = $incDivZ;
					$incDivZ = ($z + 1) / $radius;

					$lengthSquared = Math::lengthSquared3d($divX, $divY, $divZ);
					if($lengthSquared > 1) { // x**2 + y**2 + z**2 < r**2
						if($z === 0) {
							if($y === 0) {
								break 2;
							}
							break;
						}
						continue;
					}

					if($hollow && Math::lengthSquared3d($incDivX, $divY, $divZ) <= 1 && Math::lengthSquared3d($divX, $incDivY, $divZ) <= 1 && Math::lengthSquared3d($divX, $divY, $incDivZ) <= 1) {
						continue;
					}

					if($floorY + $y >= 0 && $floorY + $y < 256) { // TODO - Try creating 4 chunk iterators
						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);

						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX - $x, $floorY + $y, $floorZ + $z, $fullBlockId);

						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ - $z, $fullBlockId);

						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX - $x, $floorY + $y, $floorZ - $z, $fullBlockId);
					}
					if($floorY - $y >= 0 && $floorY - $y < 256) {
						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX + $x, $floorY - $y, $floorZ + $z, $fullBlockId);

						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX - $x, $floorY - $y, $floorZ + $z, $fullBlockId);

						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX + $x, $floorY - $y, $floorZ - $z, $fullBlockId);

						$stringToBlockDecoder->nextBlock($fullBlockId);
						$fillSession->setBlockAt($floorX - $x, $floorY - $y, $floorZ - $z, $fullBlockId);
					}
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$updates = $fillSession->getChanges();
		(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($updates, $player->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function makeHollowSphere(Player $player, Position $center, int $radius, string $blocks): UpdateResult {
		return $this->makeSphere($player, $center, $radius, $blocks, true);
	}

	public function makeCylinder(Player $player, Position $center, int $radius, int $height, string $blockArgs, bool $hollow = false): UpdateResult {
		$timer = new Timer();
		$center = Position::fromObject($center->floor(), $center->getWorld());

		$radius = abs($radius);
		if($radius === 0) {
			return UpdateResult::error("Radius could not be 0");
		}

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());
		if(!$stringToBlockDecoder->isValid()) {
			return UpdateResult::error("No blocks found in string $blockArgs");
		}

		$floorX = $center->getFloorX();
		$floorY = $center->getFloorY();
		$floorZ = $center->getFloorZ();

		// Optimizing Y values to belong <0;255>
		if($floorY < 0) {
			$height += $floorY;
			$floorY = 0;
		}
		if($floorY + $height > 255) {
			$height = 255 - $floorY;
		}
		$finalHeight = $height + $floorY;

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($floorX - $radius, $floorX + $radius, $floorZ - $radius, $floorZ + $radius);
		$fillSession->loadChunks($player->getWorld());

		$incDivX = 0;
		for($x = 0; $x <= $radius; ++$x) {
			$divX = $incDivX;
			$incDivX = ($x + 1) / $radius;
			$incDivZ = 0;
			for($z = 0; $z <= $radius; ++$z) {
				$divZ = $incDivZ;
				$incDivZ = ($z + 1) / $radius;

				$lengthSquared = Math::lengthSquared2d($divX, $divZ);
				if($lengthSquared > 1) { // checking if it can skip blocks outside of circle
					if($z === 0) {
						break 2;
					}
					break;
				}

				if($hollow && Math::lengthSquared2d($divX, $incDivZ) <= 1 && Math::lengthSquared2d($incDivX, $divZ) <= 1) {
					continue;
				}

				for($y = $floorY; $y < $finalHeight; ++$y) {
					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX + $x, $y, $floorZ + $z, $fullBlockId);
				}
				for($y = $floorY; $y < $finalHeight; ++$y) {
					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX - $x, $y, $floorZ + $z, $fullBlockId);
				}
				for($y = $floorY; $y < $finalHeight; ++$y) {
					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX + $x, $y, $floorZ - $z, $fullBlockId);
				}
				for($y = $floorY; $y < $finalHeight; ++$y) {
					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX - $x, $y, $floorZ - $z, $fullBlockId);
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$updates = $fillSession->getChanges();
		(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($updates, $player->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function makeHollowCylinder(Player $player, Position $center, int $radius, int $height, string $blockArgs): UpdateResult {
		return $this->makeCylinder($player, $center, $radius, $height, $blockArgs, true);
	}

	public function makePyramid(Player $player, Position $center, int $size, string $blockArgs, bool $hollow = false): UpdateResult {
		$timer = new Timer();
		$center = Position::fromObject($center->floor(), $center->getWorld());

		$size = abs($size);

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());
		if(!$stringToBlockDecoder->isValid()) {
			return UpdateResult::error("No blocks found in string $blockArgs");
		}

		$floorX = $center->getFloorX();
		$floorY = $center->getFloorY();
		$floorZ = $center->getFloorZ();

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($floorX - $size, $floorX + $size, $floorZ - $size, $floorZ + $size);

		$currentLevelHeight = $size;
		for($y = 0; $y <= $size; ++$y) {
			for($x = 0; $x <= $currentLevelHeight; ++$x) {
				for($z = 0; $z <= $currentLevelHeight; ++$z) {
					if($hollow && ($x !== $currentLevelHeight && $z !== $currentLevelHeight)) {
						continue;
					}

					if($floorY + $y < 0 || $floorY + $y > 255) {
						continue;
					}

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX - $x, $floorY + $y, $floorZ + $z, $fullBlockId);

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ - $z, $fullBlockId);

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX - $x, $floorY + $y, $floorZ - $z, $fullBlockId);
				}
			}
			$currentLevelHeight--;
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$updates = $fillSession->getChanges();
		(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($updates, $player->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function makeHollowPyramid(Player $player, Position $center, int $size, string $blockArgs): UpdateResult {
		return $this->makePyramid($player, $center, $size, $blockArgs, true);
	}

	public function makeCube(Player $player, Position $center, int $radius, string $blockArgs, bool $hollow = false): UpdateResult {
		$center = Position::fromObject($center->floor(), $center->getWorld());
		$radius = abs($radius);

		if($player->getPosition()->getY() - $radius < 0 || $player->getPosition()->getY() + $radius >= World::Y_MAX) {
			return UpdateResult::error("Shape is outside of the map!");
		}

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());
		if(!$stringToBlockDecoder->isValid()) {
			return UpdateResult::error("No blocks found in string $blockArgs");
		}

		return Filler::getInstance()->directFill($player, $center->subtract($radius, $radius, $radius), $center->add($radius, $radius, $radius), $stringToBlockDecoder, $hollow);
	}

	public function makeHollowCube(Player $player, Position $center, int $radius, string $blockArgs): UpdateResult {
		return $this->makeCube($player, $center, $radius, $blockArgs, true);
	}

	public function makeIsland(Player $player, Position $center, int $radius, int $step, string $blockArgs): UpdateResult {
		$timer = new Timer();
		$center = Position::fromObject($center->floor(), $center->getWorld());

		$radius = abs($radius);
		if($radius < 1) {
			return UpdateResult::error("Radius must be at least 1");
		}

		if($step < 1) {
			return UpdateResult::error("Step must be at least 1");
		}

		$stringToBlockDecoder = new StringToBlockDecoder($blockArgs, $player->getInventory()->getItemInHand());
		if(!$stringToBlockDecoder->isValid()) {
			return UpdateResult::error("No blocks found in string $blockArgs");
		}

		$floorY = $center->getFloorY();
		if($floorY < 0) {
			return UpdateResult::error("It is not possible to create island here");
		}

		$floorX = $center->getFloorX();
		$floorZ = $center->getFloorZ();

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($floorX - $radius, $floorX + $radius, $floorZ - $radius, $floorZ + $radius);
		$fillSession->loadChunks($player->getWorld());

		$currentRadius = (float)$radius;
		$step = 1 / $step;
		$y = $floorY;
		while($currentRadius > 0.1) {
			$incDivX = 0;
			for($x = 0; $x <= $currentRadius; ++$x) {
				$divX = $incDivX;
				$incDivX = ($x + 1) / $currentRadius;
				$incDivZ = 0;
				for($z = 0; $z <= $currentRadius; ++$z) {
					$divZ = $incDivZ;
					$incDivZ = ($z + 1) / $currentRadius;

					$lengthSquared = Math::lengthSquared2d($divX, $divZ);
					if($lengthSquared > 1) {
						if($z === 0) {
							break 2;
						}
						break;
					}

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX + $x, $y, $floorZ + $z, $fullBlockId);

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX - $x, $y, $floorZ + $z, $fullBlockId);

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX + $x, $y, $floorZ - $z, $fullBlockId);

					$stringToBlockDecoder->nextBlock($fullBlockId);
					$fillSession->setBlockAt($floorX - $x, $y, $floorZ - $z, $fullBlockId);
				}
			}

			$currentRadius -= $step;

			if(--$y < 0) {
				break;
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$updates = $fillSession->getChanges();
		(new DuplicateBlockCleanHelper())->cleanDuplicateBlocks($updates);

		SessionManager::getInstance()->getSession($player)->getReverseDataHolder()->saveUndo(new BlockStorageHolder($updates, $player->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}
}