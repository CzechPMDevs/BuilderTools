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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\Clipboard;
use czechpmdevs\buildertools\blockstorage\identifiers\SingleBlockIdentifier;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\math\Transform;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function max;
use function microtime;
use function min;

/** @deprecated */
class Copier {
	use SingletonTrait;

	public const DIRECTION_PLAYER = 0;
	public const DIRECTION_UP = 1;
	public const DIRECTION_DOWN = 2;

	public function copy(Vector3 $pos1, Vector3 $pos2, Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = (new Clipboard())->setRelativePosition($player->getPosition()->subtract(0.5, 0, 0.5)->floor());

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$clipboard->addBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		$clipboard->save();
		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);

		return UpdateResult::success(Math::selectionSize($pos1, $pos2), microtime(true) - $startTime);
	}

	public function cut(Vector3 $pos1, Vector3 $pos2, Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = (new Clipboard())->setRelativePosition($player->getPosition()->subtract(0.5, 0, 0.5)->floor());

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$clipboard->addBlockAt($x, $y, $z, $fullBlockId);

					$fillSession->setBlockAt($x, $y, $z, 0);
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$clipboard->save();
		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);

		$changes = $fillSession->getChanges();
		$changes->save();

		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function merge(Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			return UpdateResult::error("Clipboard is empty");
		}

		$clipboard->setWorld($player->getWorld());
		$clipboard->load();

		/** @phpstan-var Vector3 $relativePosition */
		$relativePosition = $clipboard->getRelativePosition();

		$fillSession = new MaskedFillSession($player->getWorld(), true, true, SingleBlockIdentifier::airIdentifier());

		$motion = $player->getPosition()->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		while($clipboard->hasNext()) {
			$clipboard->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->save();
		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function paste(Player $player): UpdateResult {
		$startTime = microtime(true);

		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			return UpdateResult::error("Clipboard is empty");
		}

		$clipboard->setWorld($player->getWorld());
		$clipboard->load();

		/** @phpstan-var Vector3 $relativePosition */
		$relativePosition = $clipboard->getRelativePosition();

		$fillSession = new FillSession($player->getWorld(), true, true);

		$motion = $player->getPosition()->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		while($clipboard->hasNext()) {
			$clipboard->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullBlockId);
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->save();

		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function rotate(Player $player, int $axis, int $rotation): void {
		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			$player->sendMessage(BuilderTools::getPrefix() . "§cYour clipboard is empty");
			return;
		}

		$transform = new Transform($clipboard);
		if($axis === Axis::Y) {
			$transform->rotateY($rotation);
		} elseif($axis === Axis::X) {
			$transform->rotateX($rotation);
		} else {
			$transform->rotateZ($rotation);
		}

		$transform->close();

		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);
	}

	public function flip(Player $player, int $axis): void {
		$clipboard = SessionManager::getInstance()->getSession($player)->getClipboardHolder()->getClipboard();
		if($clipboard === null) {
			$player->sendMessage(BuilderTools::getPrefix() . "§cYour clipboard is empty");
			return;
		}

		$transform = new Transform($clipboard);
		if($axis === Axis::X) {
			$transform->flipX();
		} elseif($axis === Axis::Y) {
			$transform->flipY();
		} else {
			$transform->flipZ();
		}

		$transform->close();

		SessionManager::getInstance()->getSession($player)->getClipboardHolder()->setClipboard($clipboard);
	}

	public function stack(Player $player, Vector3 $pos1, Vector3 $pos2, int $pasteCount, int $direction): UpdateResult {
		$startTime = microtime(true);

		$fillSession = new FillSession($player->getWorld(), false, true);

		// Copying on to block array
		$temporaryBlockArray = new BlockArray();

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);
					$temporaryBlockArray->addBlockAt($x, $y, $z, $fullBlockId);
				}
			}
		}

		if($direction !== Facing::DOWN && $direction !== Facing::UP) {
			if($direction === Facing::WEST || $direction === Facing::SOUTH) { // Moving along x-axis (z = const)
				$xSize = ($maxX - $minX) + 1;

				if($direction === Facing::WEST) {
					$fillSession->setDimensions($minX, $minX + ($xSize * $pasteCount), $minZ, $maxZ);
				} else {
					$fillSession->setDimensions($minX - ($xSize * $pasteCount), $minX, $minZ, $maxZ);
					$xSize = -$xSize;
				}

				$fillSession->loadChunks($player->getWorld());

				for($i = 1; $i < $pasteCount; ++$i) {
					$j = $i * $xSize;
					while($temporaryBlockArray->hasNext()) {
						$temporaryBlockArray->readNext($x, $y, $z, $fullBlockId);
						$fillSession->setBlockAt($x + $j, $y, $z, $fullBlockId);
					}

					// Resets the array reader
					$temporaryBlockArray->offset = 0;
				}
			} else { // Moving along z axis (x = const)
				$zSize = ($maxZ - $minZ) + 1;

				if($direction === Facing::EAST) {
					$fillSession->setDimensions($minX, $maxX, $minZ, $minZ + ($zSize * $pasteCount));
				} else {
					$fillSession->setDimensions($minX, $maxX, $minZ - ($zSize * $pasteCount), $maxZ);
					$zSize = -$zSize;
				}

				$fillSession->loadChunks($player->getWorld());

				for($i = 1; $i < $pasteCount; ++$i) {
					$j = $i * $zSize;
					while($temporaryBlockArray->hasNext()) {
						$temporaryBlockArray->readNext($x, $y, $z, $fullBlockId);
						$fillSession->setBlockAt($x, $y, $z + $j, $fullBlockId);
					}

					// Resets array reader
					$temporaryBlockArray->offset = 0;
				}
			}
		} else {
			$ySize = ($maxY - $minY) + 1;

			$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
			if($direction === Facing::DOWN) {
				$ySize = -$ySize;
			}

			$fillSession->loadChunks($player->getWorld());

			for($i = 1; $i < $pasteCount; ++$i) {
				$j = $i * $ySize;
				while($temporaryBlockArray->hasNext()) {
					$temporaryBlockArray->readNext($x, $y, $z, $fullBlockId);
					if($y >= 0 && $y <= 255) {
						$fillSession->setBlockAt($x, $y + $j, $z, $fullBlockId);
					}
				}

				// Resets array header
				$temporaryBlockArray->offset = 0;
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->save();
		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function move(Vector3 $pos1, Vector3 $pos2, Vector3 $motion, Player $player): UpdateResult {
		$startTime = microtime(true);

		$fillSession = new FillSession($player->getWorld(), false, true);

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		$finalMinX = $minX + $motion->getFloorX();
		$finalMaxX = $maxX + $motion->getFloorX();
		$finalMinY = $minY + $motion->getFloorY();
		$finalMaxY = $maxY + $motion->getFloorY();
		$finalMinZ = $minZ + $motion->getFloorZ();
		$finalMaxZ = $maxZ + $motion->getFloorZ();

		$fillSession->setDimensions(min($minX, $finalMinX), max($maxX, $finalMaxX), min($minZ, $finalMinZ), max($maxZ, $finalMaxZ));
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			$isXInside = $x >= $finalMinX && $x <= $finalMaxX;
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$isZInside = $z >= $finalMinZ && $z <= $finalMaxZ;
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $fullBlockId);

					// We remove the block if it is not inside the final area
					if(!($isXInside && $isZInside && $y >= $finalMinY && $y <= $finalMaxY)) {
						$fillSession->setBlockAt($x, $y, $z, 0);
					}

					/** @phpstan-var int $finalY */
					$finalY = $floorY + $y;
					if($finalY >= 0 && $finalY <= 255) {
						$fillSession->setBlockAt($floorX + $x, $finalY, $floorZ + $z, $fullBlockId);
					}
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());

		$changes = $fillSession->getChanges();
		Canceller::getInstance()->addStep($player, $changes);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}
}
