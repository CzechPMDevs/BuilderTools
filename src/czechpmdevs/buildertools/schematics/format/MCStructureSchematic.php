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
use czechpmdevs\buildertools\schematics\SchematicException;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use Throwable;
use function array_fill;
use function array_map;
use function count;
use function file_get_contents;
use function getcwd;
use function is_file;
use function unserialize;
use const DIRECTORY_SEPARATOR;

/**
 * MCStructSchematic is schematic format created by Mojang for structure blocks
 * - It should have different extension (.mcstructure instead of .schematic)
 */
class MCStructureSchematic implements Schematic {

	/** @var CompoundTag[] */
	private array $internalId2StatesMap;
	/** @var array<int[][]|int[][][]> */
	private array $states2InternalIdMap;

	public function load(string $rawData): BlockArray {
		$nbt = (new LittleEndianNbtSerializer())->read($rawData)->getTag();
		if(!$nbt instanceof CompoundTag) {
			throw new SchematicException("NBT root must be compound tag");
		}

		$size = $this->readVector3($nbt, "size");

		// Palette & indexes
		$this->loadMapping();

		// Blocks
		$palette = $this->readPalette($nbt);
		$indexes = $this->readIndexArray($nbt);

		// Dimensions
		$width = $size->getFloorX();
		$height = $size->getFloorY();
		$length = $size->getFloorZ();

		$blockArray = new BlockArray();

		$i = 0;
		for($x = 0; $x < $width; ++$x) {
			for($y = 0; $y < $height; ++$y) {
				for($z = 0; $z < $length; ++$z) {
					$fullBlockId = $palette[$indexes[$i]];
					$id = $fullBlockId >> 4;
					$meta = $fullBlockId & 0xf;

					if($id > 255 || $id < 0) {
						$id = 0;
					}

					$blockArray->addBlockAt($x, $y, $z, $id, $meta);
					++$i;
				}
			}
		}

		return $blockArray;
	}

	/**
	 * @throws SchematicException
	 */
	private function readVector3(CompoundTag $nbt, string $name): Vector3 {
		$tag = $nbt->getListTag($name);
		if($tag === null) {
			throw new SchematicException("List Tag $name was not found.");
		}

		return new Vector3(...$tag->getAllValues());
	}

	/**
	 * @return int[]
	 * @throws SchematicException
	 */
	private function readPalette(CompoundTag $nbt): array {
		/** @var CompoundTag[] $paletteData */
		$paletteData = $nbt->getCompoundTag("structure")->getCompoundTag("palette")->getCompoundTag("default")->getListTag("block_palette")->getAllValues(); // @phpstan-ignore-line (We provide validated values)

		$palette = [];
		foreach($paletteData as $i => $entry) {
			$palette[$i] = $this->translateBlockStateToFullBlockId($entry);
		}

		return $palette;
	}

	/**
	 * @return int[]
	 */
	private function readIndexArray(CompoundTag $nbt): array {
		/** @var ListTag $listTag */
		$listTag = $nbt->getCompoundTag("structure")->getListTag("block_indices")->get(0); // @phpstan-ignore-line (We provide valid values)
		// TODO : Find out why there is another list tag on index 1 full of -1 values

		return $listTag->getAllValues();
	}

	/**
	 * @throws SchematicException
	 */
	private function translateBlockStateToFullBlockId(CompoundTag $blockState): int {
		$name = $blockState->getString("name");
		if(!isset($this->states2InternalIdMap[$name])) {
			throw new SchematicException("Unmapped identifier $name");
		}

		/** @var array<int[]|int[][]> $data */
		$data = $this->states2InternalIdMap[$name];

		/** @var int $id */
		$id = $data["id"];
		/** @var int $meta */
		$meta = $data["meta"][$blockState->toString()] ?? 0;

		return $id << 4 | $meta;
	}

	/**
	 * Experimental, I am not sure this works
	 * @throws SchematicException
	 */
	public function save(BlockArray $blockArray): string {
		$nbt = new CompoundTag();

		// Format version
		$nbt->setInt("format_version", 1);

		$sizeData = $blockArray->getSizeData();

		$width = (($sizeData->maxX - $sizeData->minX) + 1);
		$height = (($sizeData->maxY - $sizeData->minY) + 1);
		$length = (($sizeData->maxZ - $sizeData->minZ) + 1);

		// Main information
		$this->writeVector3($nbt, "structure_world_origin", new Vector3(0, 0, 0)); // TODO: This Vector3 should represent original position in world
		$this->writeVector3($nbt, "size", new Vector3($width, $height, $length));

		// Palette & indexes
		$this->loadMapping();

		$yz = $height * $length;

		// Create index table
		$indexes = [];
		while($blockArray->hasNext()) {
			$blockArray->readNext($x, $y, $z, $id, $meta);
			$indexes[$z + ($length * $y) + ($yz * $x)] = $id << 4 | $meta;
		}

		// Making Palette
		$palette = $paletteHelper = [];
		foreach($indexes as &$fullId) {
			$state = $this->internalId2StatesMap[$fullId];

			if(isset($paletteHelper[$state->toString()])) {
				$fullId = $paletteHelper[$state->toString()];
				continue;
			}

			$targetIndex = count($paletteHelper);
			$paletteHelper[$state->toString()] = $targetIndex;
			$palette[$targetIndex] = $state;
			$fullId = $targetIndex;
		}

		$structureNbt = new CompoundTag();

		// Indices
		$indexes = new ListTag(array_map(fn(int $int) => new IntTag($int), $indexes));
		/** @phpstan-ignore-next-line */
		$anotherIndexes = new ListTag(array_fill(0, $indexes->count(), new IntTag(-1))); // Seems Mojang do it same way :D

		$structureNbt->setTag("block_indices", new ListTag([$indexes, $anotherIndexes]));

		// Entities
		$structureNbt->setTag("entities", new ListTag());

		// Palette
		$structureNbt->setTag("palette", (new CompoundTag())
			->setTag("default", (new CompoundTag())
				->setTag("block_palette", new ListTag($palette)
				)
			)
		);

		$nbt->setTag("structure", $structureNbt);

		return (new LittleEndianNbtSerializer())->write(new TreeRoot($nbt, "structure"));
	}

	private function writeVector3(CompoundTag $nbt, string $name, Vector3 $vector3): void {
		$nbt->setTag($name, new ListTag([
			new IntTag($vector3->getFloorX()),
			new IntTag($vector3->getFloorY()),
			new IntTag($vector3->getFloorZ())
		]));
	}

	/**
	 * @throws SchematicException
	 */
	private function loadMapping(): void {
		if(isset($this->blockIdMap)) {
			return;
		}

		$dataPath = getcwd() . DIRECTORY_SEPARATOR . "plugin_data" . DIRECTORY_SEPARATOR . "BuilderTools" . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR;

		if(!is_file($id2StatesPath = $dataPath . "internalId2StatesMap.serialized")) {
			throw new SchematicException($dataPath . "internalId2StatesMap.serialized was not found");
		}
		if(!is_file($states2IdPath = $dataPath . "states2InternalIdMap.serialized")) {
			throw new SchematicException($dataPath . "states2InternalIdMap.serialized was not found");
		}

		$id2StatesContents = file_get_contents($id2StatesPath);
		$states2IdContents = file_get_contents($states2IdPath);
		if($id2StatesContents === false || $states2IdContents === false) {
			throw new SchematicException("Unable to read files required for mapping");
		}

		$reader = new LittleEndianNbtSerializer();

		/** @var CompoundTag[] $id2StatesMap */
		$id2StatesMap = array_map(fn(string $val) => $reader->read($val)->getTag(), unserialize($id2StatesContents));
		/** @var array<int[][]|int[][][]> $states2IdMap */
		$states2IdMap = unserialize($states2IdContents);

		$this->internalId2StatesMap = $id2StatesMap;
		$this->states2InternalIdMap = $states2IdMap;
	}

	public static function getFileExtension(): string {
		return "mcstructure";
	}

	public static function validate(string $rawData): bool {
		try {
			$nbt = (new LittleEndianNbtSerializer())->read($rawData)->getTag();
			if(!$nbt instanceof CompoundTag) {
				return false;
			}

			// Test if palette exists
			$nbt->getCompoundTag("structure")->getCompoundTag("palette")->getCompoundTag("default")->getListTag("block_palette")->getAllValues(); // @phpstan-ignore-line (Errors are caught)

			// Test if block indexes exists
			$nbt->getCompoundTag("structure")->getListTag("block_indices")->get(0)->getValue(); // @phpstan-ignore-line (Errors are caught)

			return (!($nbt->getTag("size") instanceof ListTag)) && $nbt->getListTag("size")->count() == 3; // @phpstan-ignore-line (Errors are caught)
		} catch(Throwable $ignore) {
			return false;
		}
	}
}