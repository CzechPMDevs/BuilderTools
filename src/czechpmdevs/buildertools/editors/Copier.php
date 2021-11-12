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
use czechpmdevs\buildertools\blockstorage\identifiers\SingleBlockIdentifier;
use czechpmdevs\buildertools\blockstorage\SelectionData;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\ClipboardManager;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\utils\FlipUtil;
use czechpmdevs\buildertools\utils\RotationUtil;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function max;
use function microtime;
use function min;

class Copier {
	use SingletonTrait;

	public const DIRECTION_PLAYER = 0;
	public const DIRECTION_UP = 1;
	public const DIRECTION_DOWN = 2;

	public function copy(Vector3 $pos1, Vector3 $pos2, Player $player): EditorResult {
		$startTime = microtime(true);

		$clipboard = (new SelectionData())->setPlayerPosition($player->getPosition()->subtract(0.5, 0, 0.5)->floor());

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $id, $meta);
					$clipboard->addBlockAt($x, $y, $z, $id, $meta);
				}
			}
		}

		$clipboard->save();
		ClipboardManager::saveClipboard($player, $clipboard);

		return EditorResult::success(Math::selectionSize($pos1, $pos2), microtime(true) - $startTime);
	}

	public function cut(Vector3 $pos1, Vector3 $pos2, Player $player): EditorResult {
		$startTime = microtime(true);

		$clipboard = (new SelectionData())->setPlayerPosition($player->getPosition()->subtract(0.5, 0, 0.5)->floor());

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = new FillSession($player->getWorld(), false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($player->getWorld());

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $id, $meta);
					$clipboard->addBlockAt($x, $y, $z, $id, $meta);

					$fillSession->setBlockAt($x, $y, $z, 0, 0);
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$clipboard->save();
		ClipboardManager::saveClipboard($player, $clipboard);

		$changes = $fillSession->getChanges();
		$changes->save();

		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function merge(Player $player): EditorResult {
		if(!ClipboardManager::hasClipboardCopied($player)) {
			return EditorResult::error("Clipboard is empty");
		}

		$startTime = microtime(true);

		/** @phpstan-var SelectionData $clipboard */
		$clipboard = ClipboardManager::getClipboard($player);
		$clipboard->setWorld($player->getWorld());
		$clipboard->load();

		/** @phpstan-var Vector3 $relativePosition */
		$relativePosition = $clipboard->getPlayerPosition();

		$fillSession = new MaskedFillSession($player->getWorld(), true, true, SingleBlockIdentifier::airIdentifier());

		$motion = $player->getPosition()->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		while($clipboard->hasNext()) {
			$clipboard->readNext($x, $y, $z, $id, $meta);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $id, $meta);
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->save();
		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function paste(Player $player): EditorResult {
		if(!ClipboardManager::hasClipboardCopied($player)) {
			return EditorResult::error("Clipboard is empty");
		}

		$startTime = microtime(true);

		/** @phpstan-var SelectionData $clipboard */
		$clipboard = ClipboardManager::getClipboard($player);
		$clipboard->setWorld($player->getWorld());
		$clipboard->load();

		/** @phpstan-var Vector3 $relativePosition */
		$relativePosition = $clipboard->getPlayerPosition();

		$fillSession = new FillSession($player->getWorld(), true, true);

		$motion = $player->getPosition()->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		while($clipboard->hasNext()) {
			$clipboard->readNext($x, $y, $z, $id, $meta);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $id, $meta);
		}

		$fillSession->reloadChunks($player->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();
		$changes->save();

		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function rotate(Player $player, int $axis, int $rotation): void {
		if(!ClipboardManager::hasClipboardCopied($player)) {
			$player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
			return;
		}

		/** @phpstan-var SelectionData $clipboard */
		$clipboard = ClipboardManager::getClipboard($player);
		$clipboard->load();

		$clipboard = RotationUtil::rotate($clipboard, $axis, $rotation);
		$clipboard->save();

		ClipboardManager::saveClipboard($player, $clipboard);
	}

	public function flip(Player $player, int $axis): void {
		if(!ClipboardManager::hasClipboardCopied($player)) {
			$player->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
			return;
		}

		/** @phpstan-var SelectionData $clipboard */
		$clipboard = ClipboardManager::getClipboard($player);
		$clipboard->load();

		$clipboard = FlipUtil::flip($clipboard, $axis);
		$clipboard->save();

		ClipboardManager::saveClipboard($player, $clipboard);
	}

	public function stack(Player $player, Vector3 $pos1, Vector3 $pos2, int $pasteCount, int $mode = Copier::DIRECTION_PLAYER): EditorResult {
		$startTime = microtime(true);

		$fillSession = new FillSession($player->getWorld(), false, true);

		// Copying on to block array
		$temporaryBlockArray = new BlockArray();

		Math::calculateMinAndMaxValues($pos1, $pos2, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				for($y = $minY; $y <= $maxY; ++$y) {
					$fillSession->getBlockAt($x, $y, $z, $id, $meta);
					$temporaryBlockArray->addBlockAt($x, $y, $z, $id, $meta);
				}
			}
		}

		$direction = Math::getPlayerDirection($player);
		if($mode == self::DIRECTION_PLAYER) {
			if($direction == 0 || $direction == 2) { // Moving along x axis (z = const)
				$xSize = ($maxX - $minX) + 1;

				if($direction == 0) {
					$fillSession->setDimensions($minX, $minX + ($xSize * $pasteCount), $minZ, $maxZ);
				} else {
					$fillSession->setDimensions($minX - ($xSize * $pasteCount), $minX, $minZ, $maxZ);
					$xSize = -$xSize;
				}

				$fillSession->loadChunks($player->getWorld());

				for($i = 1; $i < $pasteCount; ++$i) {
					$j = $i * $xSize;
					while($temporaryBlockArray->hasNext()) {
						$temporaryBlockArray->readNext($x, $y, $z, $id, $meta);
						$fillSession->setBlockAt($x + $j, $y, $z, $id, $meta);
					}

					// Resets the array reader
					$temporaryBlockArray->offset = 0;
				}
			} else { // Moving along z axis (x = const)
				$zSize = ($maxZ - $minZ) + 1;

				if($direction == 1) {
					$fillSession->setDimensions($minX, $maxX, $minZ, $minZ + ($zSize * $pasteCount));
				} else {
					$fillSession->setDimensions($minX, $maxX, $minZ - ($zSize * $pasteCount), $maxZ);
					$zSize = -$zSize;
				}

				$fillSession->loadChunks($player->getWorld());

				for($i = 1; $i < $pasteCount; ++$i) {
					$j = $i * $zSize;
					while($temporaryBlockArray->hasNext()) {
						$temporaryBlockArray->readNext($x, $y, $z, $id, $meta);
						$fillSession->setBlockAt($x, $y, $z + $j, $id, $meta);
					}

					// Resets array reader
					$temporaryBlockArray->offset = 0;
				}
			}
		} else {
			$ySize = ($maxY - $minY) + 1;

			$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
			if($mode == Copier::DIRECTION_DOWN) {
				$ySize = -$ySize;
			}

			$fillSession->loadChunks($player->getWorld());

			for($i = 1; $i < $pasteCount; ++$i) {
				$j = $i * $ySize;
				while($temporaryBlockArray->hasNext()) {
					$temporaryBlockArray->readNext($x, $y, $z, $id, $meta);
					if($y >= 0 && $y <= 255) {
						$fillSession->setBlockAt($x, $y + $j, $z, $id, $meta);
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

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function move(Vector3 $pos1, Vector3 $pos2, Vector3 $motion, Player $player): EditorResult {
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
					$fillSession->getBlockAt($x, $y, $z, $id, $meta);

					// We remove the block if it is not inside the final area
					if(!($isXInside && $isZInside && $y >= $finalMinY && $y <= $finalMaxY)) {
						$fillSession->setBlockAt($x, $y, $z, 0, 0);
					}

					/** @phpstan-var int $finalY */
					$finalY = $floorY + $y;
					if($finalY >= 0 && $finalY <= 255) {
						$fillSession->setBlockAt($floorX + $x, $finalY, $floorZ + $z, $id, $meta);
					}
				}
			}
		}

		$fillSession->reloadChunks($player->getWorld());

		$changes = $fillSession->getChanges();
		Canceller::getInstance()->addStep($player, $changes);

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}
}
