<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\schematics\format;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\schematics\ReadonlySchematic;
use czechpmdevs\buildertools\schematics\SchematicException;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtException;
use pocketmine\nbt\NoSuchTagException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\UnexpectedTagTypeException;
use pocketmine\network\mcpe\convert\TypeConverter;
use function array_key_exists;
use function get_class;
use function is_int;
use function zlib_decode;

/**
 * Allows loading structures used by Minecraft: Bedrock Edition generator
 * Those structures are located in data/structures/* in BDS, or in the extracted
 * game data folder.
 */
class NBTStructureSchematic implements Schematic {
	use ReadonlySchematic;

	public function load(string $rawData): BlockArray {
		/** @phpstan-ignore-next-line */
		$nbt = (new BigEndianNbtSerializer())->read(zlib_decode($rawData))->getTag();
		if(!$nbt instanceof CompoundTag) {
			throw new SchematicException("NBT root must be compound tag");
		}

		$palette = $nbt->getListTag("palette");
		if($palette === null) {
			throw new SchematicException("Palette tag does not exist");
		}

		$blocks = $nbt->getListTag("blocks");
		if($blocks === null) {
			throw new SchematicException("Blocks tag does not exist");
		}

		$blockPalette = $this->decodePalette($palette);

		$blockArray = new BlockArray();
		foreach($blocks->getValue() as $blockTag) {
			if(!$blockTag instanceof CompoundTag) {
				throw new SchematicException("Block tag is not compound tag");
			}

			try {
				$pos = $blockTag->getListTag("pos");
				$state = $blockTag->getInt("state");
			} catch(NoSuchTagException | UnexpectedTagTypeException $e) {
				throw new SchematicException($e->getMessage(), $e->getCode(), $e);
			}

			if($pos === null) {
				throw new SchematicException("No tag found for position");
			}

			if(!array_key_exists($state, $blockPalette)) {
				throw new SchematicException("Block palette is not synchronised with blocks correctly. (State $state was not found)");
			}

			$blockArray->addBlock($this->readVector3($pos), $blockPalette[$state]);
		}

		return $blockArray;
	}

	/**
	 * @throws SchematicException
	 */
	private function readVector3(ListTag $tag): Vector3 {
		/** @var int[] $values */
		$values = $tag->getAllValues();
		for($i = 0; $i < 3; ++$i) {
			if(!is_int($values[$i] ?? null)) {
				throw new SchematicException("Invalid int Vector3 format.");
			}
		}

		return new Vector3(...$values);
	}

	/**
	 * @return array<int, int>
	 *
	 * @throws SchematicException
	 */
	private function decodePalette(ListTag $palette): array {
		$mapping = TypeConverter::getInstance()->getBlockTranslator();
		$dictionary = $mapping->getBlockStateDictionary();

		$fallbackStateId = VanillaBlocks::AIR()->getStateId();

		$blocks = [];
		foreach($palette->getValue() as $tag) {
			if(!$tag instanceof CompoundTag) {
				throw new SchematicException("Got invalid nbt tag in block palette - expected CompoundTag, got " . get_class($tag) . ".");
			}

			$blockStateData = $this->nbtToBlockState($tag);
			$stateId = $dictionary->lookupStateIdFromData($blockStateData);
			if($stateId === null) {
				echo "State not found for {$blockStateData->getName()}\n";
				$stateId = $fallbackStateId;
			}

			$blocks[] = $stateId;
		}

		return $blocks;
	}

	/**
	 * @throws SchematicException
	 */
	private function nbtToBlockState(CompoundTag $nbt): BlockStateData {
		try {
			$name = $nbt->getString("Name");
			$states = $nbt->getCompoundTag("Properties");
		} catch(NbtException $e) {
			throw new SchematicException($e->getMessage(), $e->getCode(), $e);
		}

		return new BlockStateData($name, $states?->getValue() ?? [], BlockStateData::CURRENT_VERSION);
	}

	public static function getFileExtension(): string {
		return "nbt";
	}

	public static function validate(string $rawData): bool {
		return true; // TODO
	}
}
