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

namespace czechpmdevs\buildertools\async\schematics;

use czechpmdevs\buildertools\async\BuilderToolsAsyncTask;
use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\schematics\format\MCEditSchematic;
use czechpmdevs\buildertools\schematics\SchematicException;
use function file_put_contents;
use function serialize;
use function unserialize;

class SchematicCreateTask extends BuilderToolsAsyncTask {

    /** @var string */
    public string $targetFilePath;
    /** @var string */
    public string $blockArray;

    /** @var string|null */
    public ?string $error = null;

    public function __construct(string $targetFilePath, BlockArray $blockArray) {
        $this->targetFilePath = $targetFilePath;
        $this->blockArray = serialize($blockArray);
    }

    public function onRun() {
        $blockArray = unserialize($this->blockArray);
        if(!$blockArray instanceof BlockArray) {
            $this->error = "Error whilst moving block array on to another thread";
            return;
        }

        try {
            $schematic = new MCEditSchematic();
            $rawData = $schematic->save($blockArray);
        }
        catch (SchematicException $exception) {
            $this->error = $exception->getMessage();
            return;
        }

        file_put_contents($this->targetFilePath, $rawData);
    }
}