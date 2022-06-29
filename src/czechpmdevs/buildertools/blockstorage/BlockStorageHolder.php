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

use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use czechpmdevs\buildertools\editors\object\FillSession;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\World;
use RuntimeException;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

class BlockStorageHolder {
	protected CompressedBlockArray $blockStorage;

	public function __construct(
		BlockArray $blockArray,
		protected ?World $world = null
	) {
		$this->blockStorage = new CompressedBlockArray($blockArray);
//		unset($blockArray);
	}

	public function getBlockStorage(): BlockArray {
		return $this->blockStorage->asBlockArray();
	}

	public function getSize(): int {
		return $this->blockStorage->getSize();
	}

	public function getWorld(): World {
		if($this->world === null) {
			throw new AssumptionFailedError("Requested world from non-world-specified Block Storage");
		}

		return $this->world;
	}

	/**
	 * @return BlockStorageHolder Changes done during insertion
	 */
	public function insert(): BlockStorageHolder {
		$fillSession = new FillSession($this->getWorld());

		$iterator = new BlockArrayIteratorHelper($this->getBlockStorage());
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullBlockId);
			$fillSession->setBlockIdAt($x, $y, $z, $fullBlockId);
		}

		$fillSession->reloadChunks($this->getWorld());
		$fillSession->close();

		return new BlockStorageHolder($fillSession->getChanges(), $this->getWorld());
	}

	protected function nbtSerialize(CompoundTag $nbt): void {}

	public function saveToNbt(): string {
		$nbt = $this->blockStorage->nbtSerialize();

		$this->nbtSerialize($nbt);


		$serializer = new BigEndianNbtSerializer();
		$buffer = zlib_encode($serializer->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);
		if($buffer === false) {
			throw new RuntimeException("Unable to serialize Block Storage.");
		}

		return $buffer;
	}

	protected function nbtDeserialize(CompoundTag $nbt): void {}

	public static function loadFromNbt(string $buffer): self {
		$data = zlib_decode($buffer);
		if($data === false) {
			throw new RuntimeException("Unable to deserialize Block Storage.");
		}

		$serializer = new BigEndianNbtSerializer();
		$nbt = $serializer->read($data)->mustGetCompoundTag();
		if(!(
			$nbt->getTag("Coords") instanceof ByteArrayTag &&
			$nbt->getTag("Blocks") instanceof ByteArrayTag
		)) {
			throw new RuntimeException("Invalid Block Storage format received");
		}


		$compressedStorage = CompressedBlockArray::nbtDeserialize($nbt);

		$instance = new self(new BlockArray(), null);
		$instance->blockStorage = $compressedStorage;

		$instance->nbtDeserialize($nbt);

		return $instance;
	}
}