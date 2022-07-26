<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\blockstorage\compressed;

use czechpmdevs\buildertools\blockstorage\TileArray;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\AssumptionFailedError;
use function array_values;
use function pack;
use function unpack;

class CompressedTileArray {
	protected string $compressedCoords;
	protected string $compressedTiles;

	protected int $size;

	public function __construct(TileArray $tileArray) {
		$this->compressedCoords = pack("q*", ...$tileArray->getCoordsArray());
		$this->compressedTiles = $this->serializeTiles($tileArray->getTilesArray());

		$this->size = $tileArray->size();
	}

	/**
	 * @return int
	 */
	public function getSize(): int {
		return $this->size;
	}

	public function asTileArray(): TileArray {
		$tileArray = new TileArray();

		$coords = unpack("q*", $this->compressedCoords);
		if($coords === false) {
			throw new AssumptionFailedError("Error whilst decompressing tile array");
		}

		$tileArray->setCoordsArray(array_values($coords));
		$tileArray->setTileArray($this->deserializeTiles($this->compressedTiles));

		return $tileArray;
	}

	public function nbtSerialize(): CompoundTag {
		$nbt = new CompoundTag();
		$nbt->setByteArray("Coords", $this->compressedCoords);
		$nbt->setByteArray("Tiles", $this->compressedTiles);

		return $nbt;
	}


	public static function nbtDeserialize(CompoundTag $nbt): self {
		$instance = new self(new TileArray());
		$instance->compressedCoords = $nbt->getByteArray("Coords");
		$instance->compressedTiles = $nbt->getByteArray("Tiles");

		return $instance;
	}

	/**
	 * @param CompoundTag[] $tiles
	 */
	private function serializeTiles(array $tiles): string {
		$nbt = new CompoundTag();
		foreach($tiles as $i => $tile) {
			$nbt->setTag((string)$i, $tile);
		}

		return (new BigEndianNbtSerializer())->write(new TreeRoot($nbt));
	}

	/**
	 * @return CompoundTag[]
	 */
	private function deserializeTiles(string $buffer): array {
		$tiles = [];

		$nbt = (new BigEndianNbtSerializer())->read($buffer)->mustGetCompoundTag();
		foreach($nbt->getValue() as $index => $value) {
			if(!$value instanceof CompoundTag) {
				// TODO - Exception?
				continue;
			}
			$tiles[(int)$index] = $value;
		}

		return $tiles;
	}
}