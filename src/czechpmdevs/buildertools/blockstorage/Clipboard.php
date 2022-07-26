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

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use RuntimeException;
use function is_int;
use function pack;
use function unpack;

class Clipboard extends BlockStorageHolder {
	protected Vector3 $relativePosition;

	public function __construct(BlockArray $blockArray, TileArray $tileArray, Vector3 $relativePosition, ?World $world = null) {
		parent::__construct($blockArray, $tileArray, $world);

		$this->relativePosition = $relativePosition;
	}

	protected function nbtSerialize(CompoundTag $nbt): void {
		$nbt->setByteArray("RelativePosition", pack("q", World::blockHash(
			$this->relativePosition->getFloorX(),
			$this->relativePosition->getFloorY(),
			$this->relativePosition->getFloorZ()
		)));
	}

	protected function nbtDeserialize(CompoundTag $nbt): void {
		$data = unpack("q", $nbt->getByteArray("RelativePosition"));
		if($data === false || !isset($data[1]) || !is_int($data[1])) {
			throw new RuntimeException("Nbt does not contain Relative position data");
		}

		World::getBlockXYZ($data[1], $x, $y, $z);
		$this->relativePosition = new Vector3($x, $y, $z);
	}

	public function setRelativePosition(Vector3 $relativePosition): self {
		$this->relativePosition = $relativePosition->floor();

		return $this;
	}

	public function getRelativePosition(): Vector3 {
		return $this->relativePosition;
	}
}