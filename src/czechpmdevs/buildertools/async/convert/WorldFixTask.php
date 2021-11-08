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

namespace czechpmdevs\buildertools\async\convert;

use czechpmdevs\buildertools\editors\Fixer;
use Generator;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\exception\CorruptedChunkException;
use pocketmine\world\format\io\leveldb\LevelDB;
use pocketmine\world\format\io\region\Anvil;
use pocketmine\world\format\io\region\CorruptedRegionException;
use pocketmine\world\format\io\region\McRegion;
use pocketmine\world\format\io\WorldProviderManager;
use Throwable;
use function basename;
use function count;
use function explode;
use function get_class;
use function glob;
use function is_dir;
use function microtime;
use function round;
use const DIRECTORY_SEPARATOR;

// TODO
class WorldFixTask extends AsyncTask {

	public string $worldPath;

	public string $error = "";

	public bool $done = false;

	public int $percent = 0;

	public float $time = 0.0;

	public int $chunkCount = 0;

	public bool $forceStop = false;

	public function __construct(string $worldPath) {
		$this->worldPath = $worldPath;
	}

	/** @noinspection PhpUnused */
	public function onRun(): void {
		if(!is_dir($this->worldPath)) {
			$this->error = "File not found";
			return;
		}

		$providerManager = new WorldProviderManager(); // TODO
		$worldProviderManagerEntry = null;
		foreach($providerManager->getMatchingProviders($this->worldPath) as $worldProviderManagerEntry) {
			break;
		}

		if($worldProviderManagerEntry === null) {
			$this->error = "Unknown provider";
			return;
		}

		try {
			$provider = $worldProviderManagerEntry->fromPath($this->worldPath . DIRECTORY_SEPARATOR);
		} catch(Throwable $error) {
			$this->error = "Error while loading provider: {$error->getMessage()}";
			return;
		}

		if((!$provider instanceof Anvil) && (!$provider instanceof LevelDB)) {
			$this->error = "BuilderTools does not support fixing chunks with " . get_class($provider) . " provider.";
			return;
		}

		$startTime = microtime(true);

		$fixer = Fixer::getInstance();

		$maxY = $provider->getWorldMaxY();
		$chunksFixed = $regionsFixed = 0;

		foreach($this->getListOfChunksToFix($this->worldPath, $regionCount) as $chunksToFix) {

			/**
			 * @var int $chunkX
			 * @var int $chunkZ
			 */
			foreach($chunksToFix as [$chunkX, $chunkZ]) {
				try {
					$chunk = $provider->loadChunk($chunkX, $chunkZ)?->getChunk();
				} catch(CorruptedChunkException $e) {
//                    MainLogger::getLogger()->warning("[BuilderTools] Chunk $chunkX:$chunkZ is corrupted. Area from X=" . ($chunkX << 4) . ",Z=" . ($chunkZ << 4) . " to X=" . (($chunkX << 4) + 15) .",Z=" . (($chunkZ << 4) + 15) . " might not have been fixed.");
					continue;
				}

				if($chunk === null) {
					continue;
				}

				$fixer->convertJavaToBedrockChunk($chunk, $maxY);

				$chunksFixed++;

				if($this->forceStop) {
					return;
				}
			}

			$percent = round(((++$regionsFixed) * 100) / $regionCount, 3);
//            $timePerChunk = round((microtime(true) - $startTime) / $chunksFixed, 3);
//            $timePerRegion = (microtime(true) - $startTime) / $regionsFixed;
//            $expectedTime = gmdate("H:i:s", (int)ceil($timePerRegion * ($regionCount - $regionsFixed)));

//            MainLogger::getLogger()->debug("[BuilderTools] World is fixed from $percent% ($regionsFixed/$regionCount regions), $chunksFixed chunks fixed with speed of $timePerChunk seconds per chunk. Expected time: $expectedTime.");

			$this->percent = (int)$percent;
			$provider->doGarbageCollection();
		}

		$this->time = round(microtime(true) - $startTime);
//        MainLogger::getLogger()->debug("[BuilderTools] World fixed in $this->time seconds, affected $chunksFixed chunks!");

		$this->done = true;
		$this->chunkCount = $chunksFixed;
	}

	/**
	 * @phpstan-return Generator<int[][]>
	 */
	private function getListOfChunksToFix(string $worldPath, ?int &$regionCount = null): Generator {
		$regionPath = $worldPath . DIRECTORY_SEPARATOR . "region" . DIRECTORY_SEPARATOR;

		$files = glob($regionPath . "*.mca*");
		if($files === false) {
			return [];
		}

		$regionCount = count($files);

		$chunks = [];
		foreach($files as $regionFilePath) {
			$split = explode(".", basename($regionFilePath));
			$regionX = (int)$split[1];
			$regionZ = (int)$split[2];

			try {
				$region = new McRegion($regionFilePath);
			} catch(CorruptedRegionException $e) {
//                MainLogger::getLogger()->warning("[BuilderTools] Region $regionX:$regionZ (File $regionFilePath) is corrupted. Area from X=" . ($regionX << 9) . ",Z=" . ($regionZ << 9) . " to X=" . ((($regionX + 1) << 9) - 1) .",Z=" . ((($regionZ + 1) << 9) - 1) . " might not have been fixed.");
				continue;
			}

			for($x = 0; $x < 32; ++$x) {
				for($z = 0; $z < 32; ++$z) {
					if($region->loadChunk($x, $z) !== null) {
						$chunks[] = [($regionX << 5) + $x, ($regionZ << 5) + $z];
					}
				}
			}

			yield $chunks;
			$chunks = [];
		}
	}
}