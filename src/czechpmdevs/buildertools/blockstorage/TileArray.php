<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use function in_array;

class TileArray {
	/** @var int[] */
	public array $coords = [];
	/** @var CompoundTag[] */
	public array $tiles = [];

	private int $lastHash;

	public function __construct(
		protected bool $detectDuplicates = false
	) {}

	public function addTileAt(int $x, int $y, int $z, CompoundTag $tile): TileArray {
		$this->lastHash = World::blockHash($x, $y, $z);

		if($this->detectDuplicates && in_array($this->lastHash, $this->coords, true)) {
			return $this;
		}

		$this->coords[] = $this->lastHash;
		$this->tiles[] = clone $tile;

		return $this;
	}

	public function size(): int {
		return count($this->coords);
	}

	/**
	 * Adds Vector3 to all the tiles in BlockArray
	 */
	public function addVector3(Vector3 $vector3): TileArray {
		$floorX = $vector3->getFloorX();
		$floorY = $vector3->getFloorY();
		$floorZ = $vector3->getFloorZ();

		$tileArray = new TileArray();
		$tileArray->tiles = $this->tiles;

		foreach($this->coords as $hash) {
			World::getBlockXYZ($hash, $x, $y, $z);
			$tileArray->coords[] = World::blockHash(($floorX + $x), ($floorY + $y), ($floorZ + $z));
		}

		return $tileArray;
	}

	/**
	 * Subtracts Vector3 from all the tiles in BlockArray
	 */
	public function subtractVector3(Vector3 $vector3): TileArray {
		return $this->addVector3($vector3->multiply(-1));
	}

	/**
	 * @param CompoundTag[] $tiles
	 */
	public function setTileArray(array $tiles): void {
		$this->tiles = $tiles;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTilesArray(): array {
		return $this->tiles;
	}

	/**
	 * @param int[] $coords
	 */
	public function setCoordsArray(array $coords): void {
		$this->coords = $coords;
	}

	/**
	 * @return int[]
	 */
	public function getCoordsArray(): array {
		return $this->coords;
	}
}