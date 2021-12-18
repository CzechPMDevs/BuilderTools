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
use czechpmdevs\buildertools\schematics\format\Schematic;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use RuntimeException;
use function basename;
use function file_exists;
use function file_get_contents;
use function serialize;

class SchematicLoadTask extends BuilderToolsAsyncTask {

	public string $file;
	public string $name;

	public string $blockArray;

	public function __construct(string $file) {
		parent::__construct();

		$this->file = $file;
	}

	public function execute(): void {
		if(!file_exists($this->file)) {
			throw new RuntimeException("File not found");
		}

		$rawData = file_get_contents($this->file);
		if($rawData === false) {
			throw new RuntimeException("Could not read file $this->file");
		}

		SchematicsManager::lazyInit();

		$format = SchematicsManager::getSchematicFormat($rawData);
		if($format === null) {
			throw new RuntimeException("Unrecognised schematics format");
		}

		/** @var Schematic $schematic */
		$schematic = new $format;
		$blockArray = $schematic->load($rawData);

		$this->name = basename($this->file, "." . $schematic::getFileExtension());
		$this->blockArray = serialize($blockArray);
	}
}