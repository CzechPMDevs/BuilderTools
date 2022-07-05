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
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\ForwardExtendCopy;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\math\IntVector2;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\session\SelectionHolder;
use czechpmdevs\buildertools\shape\Polygon;
use czechpmdevs\buildertools\utils\StringToBlockDecoder;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use function abs;
use function ceil;
use function count;
use function max;
use function min;

class PolygonalSelection extends SelectionHolder {
	protected World $world;
	/** @var IntVector2[] */
	protected array $points = [];

	protected int $minY, $maxY;

	public function size(): int {
		$j = count($this->points);
		if($j < 3) {
			throw new RuntimeException("Attempted to get size of uncompleted polygon selection");
		}

		$area = 0;
		for($i = 0; $i < $j; ++$i) {
			$k = ($i + 1) % $j;
			$area += ($this->points[$i]->x * $this->points[$k]->y) - ($this->points[$i]->y * $this->points[$k]->x);
		}

		$area = $area * ($this->maxY - $this->minY + 1) * 0.5;
		return (int)abs(ceil($area));
	}

	public function center(): Vector3 {
		throw new RuntimeException("Unsupported operation");
	}

	public function fill(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();
		$this->assureIsUnderLimit(BuilderTools::getLimits()->getFillLimit());

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$timer = new Timer();

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->fill($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($reverseData->getSize(), $timer->time());
	}

	public function outline(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();
		$this->assureIsUnderLimit(BuilderTools::getLimits()->getFillLimit());

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$timer = new Timer();

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->outline($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($reverseData->getSize(), $timer->time());
	}

	public function walls(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();
		$this->assureIsUnderLimit(BuilderTools::getLimits()->getFillLimit());

		if($blockGenerator instanceof StringToBlockDecoder && !$blockGenerator->isValid()) {
			return throw new RuntimeException("No blocks found in string.");
		}

		$timer = new Timer();

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->walls($blockGenerator, true)
			->getReverseData();

		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($reverseData->getSize(), $timer->time());
	}

	public function stack(int $count, int $direction): UpdateResult {
		$this->assureHasPositionsSelected();
		$this->assureIsUnderLimit(BuilderTools::getLimits()->getFillLimit(), $this->size() * $count);

		$timer = new Timer();

		Math::calculateMultipleMinAndMaxValues($minX, $maxX, $minZ, $maxZ, ...$this->points);
		$fillSession = (new FillSession($this->world, false, true))
			->setDimensions($minX, $maxX, $minZ, $maxZ);

		(new ForwardExtendCopy())->stack($fillSession, (new Polygon($this->world, $this->minY, $this->maxY, $this->points)), $minX, $maxX, $this->minY, $this->maxY, $minZ, $maxZ, $count, $direction);

		$reverseData = $fillSession->reloadChunks($this->world)
			->getChanges();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($reverseData, $this->world));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	public function naturalize(?BlockIdentifierList $mask = null): UpdateResult {
		throw new RuntimeException("Unsupported operation");
	}

	public function saveToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();
		$this->assureIsUnderLimit(BuilderTools::getLimits()->getClipboardLimit());

		$timer = new Timer();

		$blockArray = new BlockArray();

		(new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->read($blockArray);

		$this->session->getClipboardHolder()->setClipboard(new Clipboard($blockArray, $relativePosition, $this->world));

		return UpdateResult::success($blockArray->size(), $timer->time());

	}

	public function cutToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult {
		$this->assureHasPositionsSelected();
		$this->assureIsUnderLimit(BuilderTools::getLimits()->getClipboardLimit());

		$timer = new Timer();

		$blockArray = new BlockArray();

		$reverseData = (new Polygon($this->world, $this->minY, $this->maxY, $this->points, $mask))
			->read($blockArray)
			->fill(SingleBlockIdentifier::airIdentifier(), true)
			->getReverseData();

		$this->session->getClipboardHolder()->setClipboard(new Clipboard($blockArray, $relativePosition, $this->world));
		$this->session->getReverseDataHolder()->saveUndo($reverseData);

		return UpdateResult::success($blockArray->size(), $timer->time());
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

	protected function assureIsUnderLimit(int $limit, ?int $expectedSize = null): void {
		if($limit === -1) {
			return;
		}

		if($expectedSize === null) {
			$expectedSize = $this->size();
		}

		if($expectedSize > $limit) {
			throw new RuntimeException("Size of the selection ($expectedSize) is bigger than the limit specified in config.yml ($limit).");
		}
	}

	private function assureHasPositionsSelected(): void {
		if(count($this->points) < 3) {
			throw new RuntimeException("At least 3 points must be selected");
		}
	}
}