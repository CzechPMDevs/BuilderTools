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

use czechpmdevs\buildertools\blockstorage\type\Biomes;
use czechpmdevs\buildertools\blockstorage\type\Blocks;
use czechpmdevs\buildertools\blockstorage\type\Tiles;
use czechpmdevs\buildertools\BuilderTools;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\ChunkManager;
use RuntimeException;
use Serializable;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

class BlockArray implements Serializable {

	protected bool $isCompressed = false;
	protected ChunkManager $world;

	private Biomes $biomes;
	private Blocks $blocks;
	private Tiles $tiles;

	public function __construct() {
		$this->biomes = new Biomes($this);
		$this->blocks = new Blocks($this);
		$this->tiles = new Tiles($this);
	}

	/**
	 * @return $this
	 */
	public function setWorld(ChunkManager $world): self {
		$this->world = $world;
		return $this;
	}

	public function getWorld(): ChunkManager {
		return $this->world;
	}

	public function getBiomes(): Biomes {
		return $this->biomes;
	}

	public function getBlocks(): Blocks {
		return $this->blocks;
	}

	public function getTiles(): Tiles {
		return $this->tiles;
	}

	public function save(): void {
		if(BuilderTools::getConfiguration()->getBoolProperty("clipboard-compression") && !$this->isCompressed) {
			$this->blocks->compress();
			$this->isCompressed = true;
		}
	}

	public function load(): void {
		if(BuilderTools::getConfiguration()->getBoolProperty("clipboard-compression")) {
			$this->blocks->decompress();
			$this->isCompressed = false;
		}
	}

	final public function compress(): void {
		if($this->isCompressed) {
			throw new AssumptionFailedError("Attempted to compress compressed BlockArray");
		}

		$this->biomes->compress();
		$this->blocks->compress();
		$this->tiles->compress();
	}

	final public function decompress(): void {
		if(!$this->isCompressed) {
			throw new AssumptionFailedError("Attempted to decompress decompressed BlockArray");
		}

		$this->biomes->decompress();
		$this->blocks->decompress();
		$this->tiles->decompress();
	}

	public function isCompressed(): bool {
		return $this->isCompressed;
	}

	protected function nbtSerialize(CompoundTag $nbt): void {
		$this->tiles->nbtSerialize($nbt);
		$this->blocks->nbtSerialize($nbt);
		$this->biomes->nbtSerialize($nbt);
	}

	protected function nbtDeserialize(CompoundTag $nbt): void {
		$this->tiles->nbtSerialize($nbt);
		$this->blocks->nbtSerialize($nbt);
		$this->biomes->nbtSerialize($nbt);
	}

	public function getSerializedNbt(): CompoundTag {
		$this->compress();

		$nbt = new CompoundTag();
		$this->nbtSerialize($nbt);
		return $nbt;
	}

	public static function deserializeNbt(CompoundTag $nbt): static {
		$static = new static();
		$static->nbtDeserialize($nbt);

		return $static;
	}

	public function serialize() {
		$this->compress();

		$nbt = new CompoundTag();
		$this->nbtSerialize($nbt);

		$data = zlib_encode((new BigEndianNbtSerializer())->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);
		if(!$data) {
			throw new RuntimeException("Unable to serialize BlockArray");
		}

		return $data;
	}

	public function unserialize($data) {
		$decodedData = zlib_decode($data);
		if(!$decodedData) {
			throw new RuntimeException("Could not deserialize BlockArray (Invalid data given)");
		}

		$this->__construct();

		$nbt = (new BigEndianNbtSerializer())->read($decodedData)->mustGetCompoundTag();
		$this->nbtDeserialize($nbt);
	}
}