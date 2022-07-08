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

namespace czechpmdevs\buildertools\world;

use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class Inserter {
	protected BlockIdentifierList $mask;
	protected World $world;
	protected Vector3 $motion;

	public function __construct(
		protected BlockStorageHolder $blockStorageHolder
	) {}

	/**
	 * @return BlockStorageHolder Changes made during insertion
	 */
	public function insert(): BlockStorageHolder {
		$fillSession = isset($this->mask) ? new MaskedFillSession($this->world) : new FillSession($this->world);
		$iterator = new BlockArrayIteratorHelper($this->blockStorageHolder->getBlockStorage());

		if(isset($this->motion)) {
			$motionX = $this->motion->getFloorX();
			$motionY = $this->motion->getFloorY();
			$motionZ = $this->motion->getFloorZ();

			while($iterator->hasNext()) {
				$iterator->readNext($x, $y, $z, $fullBlockId);
				$fillSession->setBlockAt($x + $motionX, $y + $motionY, $z + $motionZ, $fullBlockId);
			}
		} else {
			while($iterator->hasNext()) {
				$iterator->readNext($x, $y, $z, $fullBlockId);
				$fillSession->setBlockAt($x, $y, $z, $fullBlockId);
			}
		}

		$fillSession->reloadChunks($this->world);
		$fillSession->close();

		return new BlockStorageHolder($fillSession->getBlockChanges(), $fillSession->getTileChanges(), $this->world);
	}

	public function setMask(BlockIdentifierList $mask): self {
		$this->mask = $mask;
		return $this;
	}

	public function setWorld(World $world): self {
		$this->world = $world;
		return $this;
	}

	public function setMotion(Vector3 $motion): self {
		$this->motion = $motion;
		return $this;
	}
}