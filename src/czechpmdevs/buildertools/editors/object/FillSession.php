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

namespace czechpmdevs\buildertools\editors\object;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\BuilderTools;
use pocketmine\block\BlockFactory;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\ChunkManager;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;

class FillSession {
	protected SubChunkExplorer $explorer;

	protected bool $calculateDimensions;
	protected bool $saveChanges;

	protected BlockArray $changes;

	protected int $minX, $maxX;
	protected int $minZ, $maxZ;

	protected int $blocksChanged = 0;
	protected bool $error = false;

	/**
	 * @var int
	 *
	 * Variable to avoid re-allocating memory all the time
	 */
	protected int $lastHash;

	public function __construct(ChunkManager $world, bool $calculateDimensions = true, bool $saveChanges = true) {
		$this->explorer = new SubChunkExplorer($world);

		$this->calculateDimensions = $calculateDimensions;
		$this->saveChanges = $saveChanges;

		if($this->saveChanges) {
			$this->changes = (new BlockArray())->setWorld($world);
		}
	}

	/**
	 * Requests block coordinates (not chunk ones)
	 */
	public function setDimensions(int $minX, int $maxX, int $minZ, int $maxZ): self {
		$this->minX = $minX;
		$this->maxX = $maxX;
		$this->minZ = $minZ;
		$this->maxZ = $maxZ;

		return $this;
	}

	/**
	 * @param int $y 0-255
	 */
	public function setBlockAt(int $x, int $y, int $z, int $fullBlockId): void {
		if(!$this->moveTo($x, $y, $z)) {
			return;
		}

		$this->saveChanges($x, $y, $z);

		/** @phpstan-ignore-next-line */
		$this->explorer->currentSubChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, $fullBlockId);
		++$this->blocksChanged;
	}

	/**
	 * @param int $y 0-255
	 */
	public function setBlockIdAt(int $x, int $y, int $z, int $id): void {
		if(!$this->moveTo($x, $y, $z)) {
			return;
		}

		$this->saveChanges($x, $y, $z);

		/** @phpstan-ignore-next-line */
		$this->explorer->currentSubChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, $id << 4);
		++$this->blocksChanged;
	}

	/**
	 * @param int $y 0-255
	 */
	public function getBlockAt(int $x, int $y, int $z, ?int &$fullBlockId = 0): void {
		if(!$this->moveTo($x, $y, $z)) {
			return;
		}

		/** @phpstan-ignore-next-line */
		$fullBlockId = $this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);
	}

	/**
	 * @param int $y 0-255
	 */
	public function getBlockIdAt(int $x, int $y, int $z, ?int &$id): void {
		if(!$this->moveTo($x, $y, $z)) {
			return;
		}

		/** @phpstan-ignore-next-line */
		$this->lastHash = $this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);

		$id = $this->lastHash >> 4;
	}

	public function setBiomeAt(int $x, int $z, int $id): void {
		if(!$this->explorer->moveTo($x, 0, $z)) {
			return;
		}

		/** @phpstan-ignore-next-line */
		$this->explorer->currentChunk->setBiomeId($x & 0xf, $z & 0xf, $id);
		++$this->blocksChanged;
	}

	public function getHighestBlockAt(int $x, int $z, ?int &$y = null): bool {
		for($y = 255; $y >= 0; --$y) {
			$this->explorer->moveTo($x, $y, $z);

			/** @phpstan-ignore-next-line */
			$id = $this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);
			if($id >> 4 !== 0) {
				if(BlockFactory::getInstance()->get($id >> 4, $id & 0xf)->isSolid()) {
					$y++;
					return true;
				}

				return false;
			}
		}
		return false;
	}

	public function getChanges(): BlockArray {
		if(!isset($this->changes)) {
			throw new AssumptionFailedError("Could not request non-saved changes");
		}

		return $this->changes;
	}

	public function getBlocksChanged(): int {
		return $this->blocksChanged;
	}

	public function loadChunks(World $world): self {
		$minX = $this->minX >> 4;
		$maxX = $this->maxX >> 4;
		$minZ = $this->minZ >> 4;
		$maxZ = $this->maxZ >> 4;

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$chunk = $world->getChunk($x, $z);
				if($chunk === null) {
					$world->loadChunk($x, $z);
				}
			}
		}

		return $this;
	}

	public function reloadChunks(World $world): self {
		if($this->error) {
			BuilderTools::getInstance()->getLogger()->notice("Some chunks were not found");
		}

		// If dimensions are not set, there should not be any blocks changed within fill session.
		// Also, it is not needed to reload chunks in that case
		if(!isset($this->minX) || !isset($this->maxX) || !isset($this->minZ) || !isset($this->maxZ)) {
			BuilderTools::getInstance()->getLogger()->debug("Requested to reload chunks for empty fill session");
			return $this;
		}

		$minX = $this->minX >> 4;
		$maxX = $this->maxX >> 4;
		$minZ = $this->minZ >> 4;
		$maxZ = $this->maxZ >> 4;

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$chunk = $world->getChunk($x, $z);
				if($chunk === null) {
					continue;
				}

				$world->setChunk($x, $z, $chunk);
				foreach($world->getChunkPlayers($x, $z) as $player) {
					$player->doChunkRequests();
				}
			}
		}

		return $this;
	}

	protected function moveTo(int $x, int $y, int $z): bool {
		if($this->explorer->moveTo($x, $y, $z) === SubChunkExplorerStatus::INVALID) {
			return false;
		}

		if($this->calculateDimensions) {
			if((!isset($this->minX)) || $x < $this->minX) $this->minX = $x;
			if((!isset($this->minZ)) || $z < $this->minZ) $this->minZ = $z;
			if((!isset($this->maxX)) || $x > $this->maxX) $this->maxX = $x;
			if((!isset($this->maxZ)) || $z > $this->maxZ) $this->maxZ = $z;
		}

		return true;
	}

	protected function saveChanges(int $x, int $y, int $z): void {
		if($this->saveChanges) {
			/** @phpstan-ignore-next-line */
			$this->changes->addBlockAt($x, $y, $z, $this->explorer->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf));
		}
	}

	public function close(): void {
		$this->explorer->invalidate();
	}
}