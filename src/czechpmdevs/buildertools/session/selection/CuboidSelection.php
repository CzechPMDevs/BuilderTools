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

namespace czechpmdevs\buildertools\session\selection;

use Closure;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\editors\Copier;
use czechpmdevs\buildertools\editors\Filler;
use czechpmdevs\buildertools\editors\Naturalizer;
use czechpmdevs\buildertools\editors\object\EditorResult;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use czechpmdevs\buildertools\session\SelectionHolder;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use function microtime;

class CuboidSelection extends SelectionHolder {
	private World $world;
	private Vector3 $firstPosition, $secondPosition;

	public function size(): int {
		$this->checkHasPositionsSelected();

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, true, $minX, $maxX, $minY, $maxY, $minZ, $maxZ);
		return (($maxX - $minX) + 1) * (($maxY - $minY) + 1) * (($maxZ - $minZ) + 1);
	}

	public function center(): Vector3 {
		$this->checkHasPositionsSelected();

		return $this->firstPosition->addVector($this->secondPosition)->divide(2);
	}

	// TODO - Mask
	public function fill(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): EditorResult {
		$this->checkHasPositionsSelected();

		return Filler::getInstance()->directFill($this->session->getPlayer(), $this->firstPosition, $this->secondPosition, $blockGenerator);
	}

	// TODO - Mask
	public function outline(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): EditorResult {
		$this->checkHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return EditorResult::error("No blocks found in string.");
		}

		return Filler::getInstance()->directFill($this->session->getPlayer(), $this->firstPosition, $this->secondPosition, $blockGenerator, true);
	}

	// TODO - Mask
	public function walls(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): EditorResult {
		$this->checkHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return EditorResult::error("No blocks found in string.");
		}

		return Filler::getInstance()->directWalls($this->session->getPlayer(), $this->firstPosition, $this->secondPosition, $blockGenerator);
	}


	public function stack(int $count, int $direction): EditorResult {
		$this->checkHasPositionsSelected();

		return Copier::getInstance()->stack($this->session->getPlayer(), $this->firstPosition, $this->secondPosition, $count, $direction);
	}

	// TODO - Mask
	public function naturalize(?BlockIdentifierList $mask = null): EditorResult {
		$this->checkHasPositionsSelected();

		return Naturalizer::getInstance()->naturalize($this->firstPosition, $this->secondPosition, $this->session->getPlayer());
	}

	// TODO - Mask
	public function saveToClipboard(?BlockIdentifierList $mask = null): EditorResult {
		$this->checkHasPositionsSelected();

		return Copier::getInstance()->copy($this->firstPosition, $this->secondPosition, $this->session->getPlayer());
	}

	// TODO - Mask
	public function cutToClipboard(?BlockIdentifierList $mask = null): EditorResult {
		$this->checkHasPositionsSelected();

		return Copier::getInstance()->cut($this->firstPosition, $this->secondPosition, $this->session->getPlayer());
	}

	// TODO - Mask
	public function saveToSchematic(string $name, Closure $callback, ?BlockIdentifierList $mask = null): void {
		$this->checkHasPositionsSelected();

		SchematicsManager::createSchematic($this->session->getPlayer(), $this->firstPosition, $this->secondPosition, $name, $callback);
	}

	private function checkHasPositionsSelected(): void {
		if(!isset($this->firstPosition)) {
			throw new RuntimeException("First position is not selected");
		}

		if(!isset($this->secondPosition)) {
			throw new RuntimeException("Second position is not selected");
		}
	}

	public function changeBiome(int $biomeId): EditorResult {
		$this->checkHasPositionsSelected();

		$startTime = microtime(true);

		Math::calculateMinAndMaxValues($this->firstPosition, $this->secondPosition, false, $minX, $maxX, $_, $_, $minZ, $maxZ);

		$fillSession = new FillSession($this->getWorld(), false, false);
		$fillSession->setDimensions($minX, $maxX, $minZ, $maxZ);
		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$fillSession->setBiomeAt($x, $z, $biomeId);
			}
		}

		$fillSession->reloadChunks($this->getWorld());

		return EditorResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function move(int $x, int $y, int $z): EditorResult {
		$this->checkHasPositionsSelected();

		return Copier::getInstance()->move($this->firstPosition, $this->secondPosition, new Vector3($x, $y, $z), $this->session->getPlayer());
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