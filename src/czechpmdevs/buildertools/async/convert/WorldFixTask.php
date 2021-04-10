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
use Error;
use Generator;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\LevelProviderManager;
use pocketmine\level\format\io\region\Anvil;
use pocketmine\level\format\io\region\RegionLoader;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\MainLogger;
use function basename;
use function explode;
use function glob;
use function is_dir;
use function microtime;
use function round;

class WorldFixTask extends AsyncTask {
    
    /** @var string */
    public string $worldPath;

    /** @var string */
    public string $error = "";
    /** @var bool */
    public bool $done = false;

    /** @var float */
    public float $time = 0.0;
    /** @var int */
    public int $chunkCount = 0;

    /** @var bool */
    public bool $forceStop = false;

    public function __construct(string $worldPath) {
        $this->worldPath = $worldPath;
    }

    /** @noinspection PhpUnused */
    public function onRun() {
        MainLogger::getLogger()->debug("[BuilderTools] Fixing world $this->worldPath...");

        if(!is_dir($this->worldPath)) {
            $this->error = "File not found";
            return;
        }

        LevelProviderManager::init();
        $providerClass = LevelProviderManager::getProvider($this->worldPath);
        if($providerClass === null) {
            $this->error = "Unknown provider";
            return;
        }

        try {
            /** @var BaseLevelProvider|Anvil|null $provider */
            $provider = new $providerClass($this->worldPath . DIRECTORY_SEPARATOR);
        }
        catch (Error $error) {
            $this->error = "Error while loading provider: {$error->getMessage()}";
            return;
        }

        if(!$provider instanceof Anvil) {
            if($provider === null) {
                $this->error = "Unknown world provider.";
                return;
            }

            $this->error = "BuilderTools does not support fixing chunks with {$provider->getName()} provider.";
            return;
        }

        $startTime = microtime(true);

        $fixer = Fixer::getInstance();

        $maxY = $provider->getWorldHeight();
        $chunksFixed = 0;

        foreach ($this->getListOfChunksToFix($this->worldPath) as $chunksToFix) {
            foreach ($chunksToFix as [$chunkX, $chunkZ]) {
                $chunk = $provider->loadChunk($chunkX, $chunkZ);
                if($chunk === null) {
                    continue;
                }

                if($fixer->convertJavaToBedrockChunk($chunk, $maxY)) {
                    $provider->saveChunk($chunk);
                }

                $chunksFixed++;
                MainLogger::getLogger()->debug("[BuilderTools] $chunksFixed chunks fixed!");
                if($this->forceStop) {
                    return;
                }
            }

            $provider->doGarbageCollection();
        }

        $this->time = round(microtime(true) - $startTime);
        MainLogger::getLogger()->debug("[BuilderTools] World fixed in $this->time, affected $chunksFixed chunks!");

        $this->done = true;
    }

    /**
     * @phpstan-return Generator<int[][]>
     */
    private function getListOfChunksToFix(string $worldPath): Generator {
        $regionPath = $worldPath . DIRECTORY_SEPARATOR . "region" . DIRECTORY_SEPARATOR;

        $files = glob($regionPath . "*.mca*");
        if($files === false) {
            return [];
        }

        $chunks = [];
        foreach ($files as $regionFilePath) {
            $split = explode(".", basename($regionFilePath));
            $regionX = (int)$split[1];
            $regionZ = (int)$split[2];

            $region = new RegionLoader($regionFilePath, $regionX, $regionZ);
            $region->open();

            for($x = 0; $x < 32; ++$x) {
                for($z = 0; $z < 32; ++$z) {
                    if($region->chunkExists($x, $z)) {
                        $chunks[] = [($regionX << 5) + $x, ($regionZ << 5) + $z];
                    }
                }
            }

            yield $chunks;
            $chunks = [];
        }
    }
}