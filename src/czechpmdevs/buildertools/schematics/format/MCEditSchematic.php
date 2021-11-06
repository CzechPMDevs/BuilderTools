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
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\schematics\SchematicException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\TreeRoot;
use Throwable;
use function chr;
use function ord;
use function str_repeat;
use function strtolower;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

class MCEditSchematic implements Schematic {

	public const MATERIALS_CLASSIC = "Classic";
	public const MATERIALS_BEDROCK = "Pocket";
	public const MATERIALS_ALPHA = "Alpha";

	/**
	 * @throws SchematicException
	 */
	public function load(string $rawData): BlockArray {
		/** @phpstan-ignore-next-line */
		$nbt = (new BigEndianNbtSerializer())->read(zlib_decode($rawData))->getTag();
		if(!$nbt instanceof CompoundTag) {
			throw new SchematicException("NBT root must be compound tag");
		}

		$this->readDimensions($nbt, $width, $height, $length);
		$this->readBlockData($nbt, $blocks, $data);
		$this->readMaterials($nbt, $materials);

		$blockArray = new BlockArray();

		$i = 0;
		for($y = 0; $y < $height; ++$y) {
			for($z = 0; $z < $length; ++$z) {
				for($x = 0; $x < $width; ++$x) {
					$id = ord($blocks[$i]);
					$meta = ord($data[$i]);

					$blockArray->addBlockAt($x, $y, $z, $id, $meta);
					++$i;
				}
			}
		}

		if($materials == MCEditSchematic::MATERIALS_CLASSIC || $materials == MCEditSchematic::MATERIALS_ALPHA) {
			$fixer = Fixer::getInstance();

			foreach($blockArray->blocks as &$fullBlock) {
				$fixer->convertJavaToBedrockId($fullBlock);
			}
		}

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
	private function readBlockData(CompoundTag $nbt, ?string &$blocks, ?string &$data): void {
		if(
			!$nbt->getTag("Blocks") instanceof ByteArrayTag ||
			!$nbt->getTag("Data") instanceof ByteArrayTag
		) {
			throw new SchematicException("NBT does not contains Block information");
		}

		$blocks = $nbt->getByteArray("Blocks");
		$data = $nbt->getByteArray("Data");
	}

	private function readMaterials(CompoundTag $nbt, ?string &$materials): void {
		if(strtolower($nbt->getString("Materials", MCEditSchematic::MATERIALS_CLASSIC)) == strtolower(MCEditSchematic::MATERIALS_BEDROCK)) {
			$materials = MCEditSchematic::MATERIALS_BEDROCK;
			return;
		}

		$materials = MCEditSchematic::MATERIALS_CLASSIC;
	}

	/**
	 * @throws SchematicException
	 */
	public function save(BlockArray $blockArray): string {
		$nbt = new CompoundTag();

		$sizeData = $blockArray->getSizeData();

		$width = (($sizeData->maxX - $sizeData->minX) + 1);
		$height = (($sizeData->maxY - $sizeData->minY) + 1);
		$length = (($sizeData->maxZ - $sizeData->minZ) + 1);

		$xz = $width * $length;
		$totalSize = $xz * $height;

		$blocks = $data = str_repeat(chr(0), $totalSize);
		while($blockArray->hasNext()) {
			$blockArray->readNext($x, $y, $z, $id, $meta);
			$key = $x + ($width * $z) + ($xz * $y);

			$blocks[$key] = chr($id);
			$data[$key] = chr($meta);
		}

		$this->writeDimensions($nbt, $width, $height, $length);

		/**
		 * @phpstan-var string $blocks
		 * @phpstan-var string $data
		 */
		$this->writeBlockData($nbt, $blocks, $data);
		$this->writeMaterials($nbt, MCEditSchematic::MATERIALS_BEDROCK);

		$rawData = zlib_encode((new BigEndianNbtSerializer())->write(new TreeRoot($nbt)), ZLIB_ENCODING_GZIP);
		if($rawData === false) {
			throw new SchematicException("Could not compress nbt");
		}

		return $rawData;
	}

	private function writeDimensions(CompoundTag $nbt, int $xSize, int $ySize, int $zSize): void {
		$nbt->setShort("Width", $xSize);
		$nbt->setShort("Height", $ySize);
		$nbt->setShort("Length", $zSize);
	}

	private function writeBlockData(CompoundTag $nbt, string $blocks, string $data): void {
		$nbt->setByteArray("Blocks", $blocks);
		$nbt->setByteArray("Data", $data);
	}

	/** @noinspection PhpSameParameterValueInspection */
	private function writeMaterials(CompoundTag $nbt, string $materials): void {
		$nbt->setString("Materials", $materials);
	}

	public static function getFileExtension(): string {
		return "schematic";
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

			// MCEdit
			if(
				$nbt->getTag("Width") instanceof ShortTag &&
				$nbt->getTag("Height") instanceof ShortTag &&
				$nbt->getTag("Length") instanceof ShortTag &&
				$nbt->getTag("Blocks") instanceof ByteArrayTag &&
				$nbt->getTag("Data") instanceof ByteArrayTag
			) {
				return true;
			}

			return false;
		} catch(Throwable $ignore) {
			return false;
		}
	}
}