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

namespace czechpmdevs\buildertools\schematics\format;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\schematics\ReadonlySchematic;
use czechpmdevs\buildertools\schematics\SchematicException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use Throwable;
use function file_get_contents;
use function getcwd;
use function is_array;
use function is_file;
use function json_decode;
use function ord;
use function zlib_decode;
use const DIRECTORY_SEPARATOR;

class SpongeSchematic implements Schematic {
	use ReadonlySchematic;

	/** @var array<string, int> */
	private array $javaBlockStatesMap = [];

	public function load(string $rawData): BlockArray {
		/** @phpstan-ignore-next-line */
		$nbt = (new BigEndianNbtSerializer())->read(zlib_decode($rawData))->mustGetCompoundTag();

		$this->readDimensions($nbt, $width, $height, $length);
		$this->loadMapping();

		$paletteTag = $nbt->getCompoundTag("Palette");
		if($paletteTag === null) {
			throw new SchematicException("Missing palette from schematic nbt");
		}

		$palette = [];
		foreach($paletteTag->getValue() as $javaState => $placeholder) {
			$palette[$placeholder->getValue()] = $this->javaBlockStatesMap[$javaState] ?? (248 << 4);
		}

		$blocks = $nbt->getByteArray("BlockData");
		$blockArray = new BlockArray();

		$i = 0;
		for($y = 0; $y < $height; ++$y) {
			for($z = 0; $z < $length; ++$z) {
				for($x = 0; $x < $width; ++$x) {
					$blockHash = $palette[ord($blocks[$i++])];

					$blockArray->addBlockAt($x, $y, $z, $blockHash >> 4, $blockHash & 0xf);
				}
			}
		}

		$this->javaBlockStatesMap = [];

		return $blockArray;
	}

	/**
	 * @throws SchematicException
	 */
	private function readDimensions(CompoundTag $nbt, ?int &$xSize, ?int &$ySize, ?int &$zSize): void {
		if(
			!$nbt->getTag("Width") instanceof ShortTag ||
			!$nbt->getTag("Height") instanceof ShortTag ||
			!$nbt->getTag("Length") instanceof ShortTag
		) {
			throw new SchematicException("NBT does not contain Dimension vector");
		}

		$xSize = $nbt->getShort("Width");
		$ySize = $nbt->getShort("Height");
		$zSize = $nbt->getShort("Length");
	}

	/**
	 * @throws SchematicException
	 */
	private function loadMapping(): void {
		$dataPath = getcwd() . DIRECTORY_SEPARATOR . "plugin_data" . DIRECTORY_SEPARATOR . "BuilderTools" . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR;

		if(!is_file($javaStatesMapPath = $dataPath . "java_block_states_map.json")) {
			throw new SchematicException($javaStatesMapPath . " was not found");
		}

		$rawJavaStatesMap = file_get_contents($javaStatesMapPath);
		if(!$rawJavaStatesMap) {
			throw new SchematicException("Could not read from $javaStatesMapPath");
		}

		$javaBlockStatesMap = json_decode($rawJavaStatesMap, true);
		if(!is_array($javaBlockStatesMap)) {
			throw new SchematicException("Invalid or corrupted resource given");
		}

		$this->javaBlockStatesMap = $javaBlockStatesMap;
	}

	public static function getFileExtension(): string {
		return "schem";
	}

	public static function validate(string $rawData): bool {
		try {
			$rawData = zlib_decode($rawData);
			if($rawData === false) {
				return false;
			}

			$nbt = (new BigEndianNbtSerializer())->read($rawData)->getTag();
			if(!$nbt instanceof CompoundTag) {
				return false;
			}

			if(
				$nbt->getTag("Width") instanceof ShortTag &&
				$nbt->getTag("Height") instanceof ShortTag &&
				$nbt->getTag("Length") instanceof ShortTag &&
				$nbt->getTag("Palette") instanceof CompoundTag &&
				$nbt->getTag("BlockData") instanceof ByteArrayTag
			) {
				return true;
			}
			return false;
		} catch(Throwable) {
			return false;
		}
	}
}