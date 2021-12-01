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

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use function pack;
use function unpack;

class SelectionData extends BlockArray {

	protected Vector3 $playerPosition;

	public function getPlayerPosition(): Vector3 {
		return $this->playerPosition;
	}

	/**
	 * @return $this
	 */
	public function setPlayerPosition(Vector3 $playerPosition): SelectionData {
		$this->playerPosition = $playerPosition->floor();

		return $this;
	}

	protected function nbtSerialize(CompoundTag $nbt): void {
		parent::nbtDeserialize($nbt);

		$nbt->setByteArray("PlayerPosition", pack("q", World::blockHash($this->playerPosition->getFloorX(), $this->playerPosition->getFloorY(), $this->playerPosition->getFloorZ())));
	}

	protected function nbtDeserialize(CompoundTag $nbt): void {
		parent::nbtDeserialize($nbt);

		/** @phpstan-ignore-next-line */
		World::getBlockXYZ((int)(unpack("q", $nbt->getByteArray("PlayerPosition"))[1]), $x, $y, $z);
		$this->playerPosition = new Vector3($x, $y, $z);
	}
}