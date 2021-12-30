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
use czechpmdevs\buildertools\blockstorage\Clipboard;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\blockstorage\identifiers\SingleBlockIdentifier;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\ForwardExtendCopy;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\math\IntVector2;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\session\SelectionHolder;
use czechpmdevs\buildertools\shape\Polygon;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use function abs;
use function count;
use function floor;
use function max;
use function microtime;
use function min;

class PolygonalSelection extends SelectionHolder {
	protected World $world;
	/** @var IntVector2[] */
	protected array $points = [];

	protected int $minY, $maxY;

	public function size(): int {
		$area = 0;
		$k = count($this->points);
		$j = $k - 1;
		for($i = 0; $i < $k; ++$i) {
			$x = $this->points[$j]->x + $this->points[$i]->x;
			$z = $this->points[$j]->y - $this->points[$i]->y;
			$area += $x * $z;
			$j = $i;
		}

		return (int)floor(abs((float)$j * 0.5)) * ($this->maxY - $this->minY + 1);
	}

	public function center(): Vector3 {
		throw new RuntimeException("Unsupported operation");
	}

	public function fill(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$startTime = microtime(true);

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->fill($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($reverseData->size(), microtime(true) - $startTime);
	}

	public function outline(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$startTime = microtime(true);

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->outline($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($reverseData->size(), microtime(true) - $startTime);
	}

	public function walls(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$startTime = microtime(true);

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->walls($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($reverseData->size(), microtime(true) - $startTime);
	}

	public function stack(int $count, int $direction): UpdateResult {
		$this->assureHasPositionsSelected();

		$startTime = microtime(true);

		Math::calculateMultipleMinAndMaxValues($minX, $maxX, $minZ, $maxZ, ...$this->points);
		$fillSession = (new FillSession($this->world, false, true))
			->setDimensions($minX, $maxX, $minZ, $maxZ);

		(new ForwardExtendCopy())->stack($fillSession, (new Polygon($this->world, $this->minY, $this->maxY, $this->points)), $minX, $maxX, $this->minY, $this->maxY, $minZ, $maxZ, $count, $direction);

		$reverseData = $fillSession->reloadChunks($this->world)
			->getChanges()
			->unload();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($fillSession->getBlocksChanged(), microtime(true) - $startTime);
	}

	public function naturalize(?BlockIdentifierList $mask = null): UpdateResult {
		throw new RuntimeException("Unsupported operation");
	}

	public function saveToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		$startTime = microtime(true);

		$clipboard = new Clipboard();
		$clipboard->setRelativePosition($relativePosition);

		(new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->read($clipboard);

		$this->session->getClipboardHolder()->setClipboard($clipboard);

		return UpdateResult::success($clipboard->size(), microtime(true) - $startTime);

	}

	public function cutToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();

		$startTime = microtime(true);

		$clipboard = new Clipboard();
		$clipboard->setRelativePosition($relativePosition);

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->read($clipboard)
			->fill(SingleBlockIdentifier::airIdentifier(), true)
			->getReverseData();

		$this->session->getClipboardHolder()->setClipboard($clipboard);
		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($clipboard->size(), microtime(true) - $startTime);
	}

	public function saveToSchematic(string $name, Closure $callback, ?BlockIdentifierList $mask = null): void {
		throw new RuntimeException("Unsupported operation");
	}

	public function changeBiome(int $biomeId): UpdateResult {
		throw new RuntimeException("Unsupported operation");
	}

	public function move(int $xMotion, int $yMotion, int $zMotion): UpdateResult {
		throw new RuntimeException("Unsupported operation");
	}

	public function getWorld(): World {
		return $this->world;
	}

	public function handleWandAxeBlockBreak(Position $position): void {
		$this->points = [];
		$this->world = $position->getWorld();
		$this->minY = $this->maxY = $position->getFloorY();
		$this->handleWandAxeBlockClick($position);
	}

	public function handleWandAxeBlockClick(Position $position): void {
		if(count($this->points) === ($limit = BuilderTools::getConfiguration()->getIntProperty("poly-points-limit"))) {
			throw new RuntimeException("2D Polygon points limit ($limit) reached");
		}
		if(isset($this->world) && $this->world->getId() !== $position->getWorld()->getId()) {
			throw new RuntimeException("Position selected in wrong world");
		}

		if(isset($this->minY)) {
			$this->minY = min($this->minY, $position->getFloorY());
			$this->maxY = max($this->maxY, $position->getFloorY());
		} else {
			$this->minY = $this->maxY = $position->getFloorY();
		}

		$this->points[] = new IntVector2($position->getFloorX(), $position->getFloorZ());
	}

	private function assureHasPositionsSelected(): void {
		if(count($this->points) < 3) {
			throw new RuntimeException("At least 3 points must be selected");
		}
	}
}