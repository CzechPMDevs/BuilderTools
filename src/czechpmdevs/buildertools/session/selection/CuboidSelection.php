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

use Closure;
use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\blockstorage\Clipboard;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\blockstorage\identifiers\SingleBlockIdentifier;
use czechpmdevs\buildertools\editors\ForwardExtendCopy;
use czechpmdevs\buildertools\editors\Naturalizer;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use czechpmdevs\buildertools\session\SelectionHolder;
use czechpmdevs\buildertools\shape\Cuboid;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use function max;
use function min;

class CuboidSelection extends SelectionHolder {
	protected World $world;
	protected Vector3 $firstPosition, $secondPosition;

	public function size(): int {
		$this->assureHasPositionsSelected();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		return (($maxX - $minX) + 1) * (($maxY - $minY) + 1) * (($maxZ - $minZ) + 1);
	}

	public function center(): Vector3 {
		$this->assureHasPositionsSelected();

		return $this->firstPosition->addVector($this->secondPosition)->divide(2);
	}

	public function fill(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		$reverseData = (new Cuboid($this->world, $minX, $maxX, $minY, $maxY, $minZ, $maxZ, $mask))
			->fill($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($reverseData->size(), $timer->time());
	}

	public function outline(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		$reverseData = (new Cuboid($this->world, $minX, $maxX, $minY, $maxY, $minZ, $maxZ, $mask))
			->outline($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($reverseData->size(), $timer->time());
	}

	public function walls(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		$reverseData = (new Cuboid($this->world, $minX, $maxX, $minY, $maxY, $minZ, $maxZ, $mask))
			->walls($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($reverseData->size(), $timer->time());
	}


	public function stack(int $count, int $direction): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		$fillSession = (new FillSession($this->world, false, true))
			->setDimensions($minX, $maxX, $minZ, $maxZ);

		(new ForwardExtendCopy())->stack($fillSession, new Cuboid($this->world, $minX, $maxX, $minY, $maxY, $minZ, $maxZ), $minX, $maxX, $minY, $maxY, $minZ, $maxZ, $count, $direction);

		$reverseData = $fillSession->reloadChunks($this->world)
			->getChanges();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function naturalize(?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();

		$naturalizer = new Naturalizer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		$fillSession = ($mask === null ? new FillSession($this->world, false, true) : new MaskedFillSession($this->world, false, true, $mask))
			->setDimensions($minX, $maxX, $minZ, $maxZ)
			->loadChunks($this->world);

		$naturalizer->naturalize($fillSession, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$reverseData = $fillSession->reloadChunks($this->world)
			->getChanges();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($reverseData->size(), $timer->time());
	}

	public function saveToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();

		$blockArray = new BlockArray();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		(new Cuboid($this->world, $minX, $maxX, $minY, $maxY, $minZ, $maxZ, $mask))
			->read($blockArray);

		$this->session->getClipboardHolder()->setClipboard(new Clipboard($blockArray, $relativePosition, $this->world));

		return UpdateResult::success($blockArray->size(), $timer->time());
	}

	public function cutToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();
		$blockStorage = new BlockArray();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		$reverseData = (new Cuboid($this->world, $minX, $maxX, $minY, $maxY, $minZ, $maxZ, $mask))
			->read($blockStorage)
			->fill(SingleBlockIdentifier::airIdentifier(), true)
			->getReverseData();

		$this->session->getClipboardHolder()->setClipboard(new Clipboard($blockStorage, $relativePosition, $this->world));
		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($blockStorage->size(), $timer->time());
	}

	// TODO - Mask
	public function saveToSchematic(string $name, Closure $callback, ?BlockIdentifierList $mask = null): void {
		$this->assureHasPositionsSelected();

		SchematicsManager::createSchematic($this->session->getPlayer(), $this->firstPosition, $this->secondPosition, $name, $callback);
	}

	private function assureHasPositionsSelected(): void {
		if(!isset($this->firstPosition)) {
			throw new RuntimeException("First position is not selected");
		}

		if(!isset($this->secondPosition)) {
			throw new RuntimeException("Second position is not selected");
		}
	}

	public function changeBiome(int $biomeId): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, false, $minX, $maxX, $_, $_, $minZ, $maxZ);

		$fillSession = new FillSession($this->getWorld(), false, false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$fillSession->setBiomeAt($x, $z, $biomeId);
			}
		}

		$fillSession->reloadChunks($this->getWorld());

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function move(int $xMotion, int $yMotion, int $zMotion): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$finalMinX = $minX + $xMotion;
		$finalMaxX = $maxX + $xMotion;
		$finalMinY = $minY + $yMotion;
		$finalMaxY = $maxY + $yMotion;
		$finalMinZ = $minZ + $zMotion;
		$finalMaxZ = $maxZ + $zMotion;

		$fillSession = new FillSession($this->world, false, true);
		$fillSession->setDimensions(min($minX, $finalMinX), max($maxX, $finalMaxX), min($minZ, $finalMinZ), max($maxZ, $finalMaxZ));
		$fillSession->loadChunks($this->world);

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

					$finalY = $yMotion + $y;
					if($finalY >= World::Y_MIN && $finalY <= World::Y_MAX) {
						$fillSession->setBlockAt($xMotion + $x, $finalY, $zMotion + $z, $fullBlockId);
					}
				}
			}
		}

		$fillSession->reloadChunks($this->world);
		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($fillSession->getChanges(), $this->world));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function createLineBetweenPositions(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		$timer = new Timer();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);

		$fillSession = $mask === null ?
			new FillSession($this->world, false, true) :
			new MaskedFillSession($this->world, false, true, $mask);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		$fillSession->loadChunks($this->world);

		foreach(VoxelRayTrace::betweenPoints($this->firstPosition, $this->secondPosition) as $pos) {
			$blockGenerator->nextBlock($fullBlockId);
			$fillSession->setBlockAt((int)$pos->getX(), (int)$pos->getY(), (int)$pos->getZ(), $fullBlockId);
		}

		$fillSession->reloadChunks($this->world);
		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($fillSession->getChanges(), $this->world));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function getWorld(): World {
		if(!isset($this->world)) {
			throw new RuntimeException("World is not set in selection");
		}

		return $this->world;
	}

	public function handleWandAxeBlockBreak(Position $position): void {
		if(isset($this->world) && $position->getWorld()->getId() !== $this->world->getId()) {
			unset($this->secondPosition);
		}

		$this->firstPosition = $position->asVector3()->floor();
		$this->world = $position->getWorld();
	}

	public function handleWandAxeBlockClick(Position $position): void {
		if(isset($this->world) && $position->getWorld()->getId() !== $this->world->getId()) {
			unset($this->firstPosition);
		}

		$this->secondPosition = $position->asVector3()->floor();
		$this->world = $position->getWorld();
	}
}