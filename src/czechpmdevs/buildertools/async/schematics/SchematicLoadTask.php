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
use czechpmdevs\buildertools\schematics\SchematicException;
use czechpmdevs\buildertools\schematics\SchematicsManager;
use function basename;
use function file_exists;
use function file_get_contents;
use function serialize;

class SchematicLoadTask extends BuilderToolsAsyncTask {

	public string $file;

	public string $name;

	public string $blockArray;

	public ?string $error = null;

	public function __construct(string $file) {
		$this->file = $file;
	}

	/** @noinspection PhpUnused */
	public function onRun(): void {
		if(!file_exists($this->file)) {
			$this->error = "File not found.";
			return;
		}

		$rawData = file_get_contents($this->file);
		if($rawData === false) {
			$this->error = "Could not read file $this->file";
			return;
		}

		SchematicsManager::lazyInit();

		$format = SchematicsManager::getSchematicFormat($rawData);
		if($format === null) {
			$this->error = "Unrecognised format";
			return;
		}

		/** @var Schematic $schematic */
		$schematic = new $format;

		try {
			$blockArray = $schematic->load($rawData);
		} catch(SchematicException $exception) {
			$this->error = $exception->getMessage();
			return;
		}

		$this->name = basename($this->file, "." . $schematic::getFileExtension());
		$this->blockArray = serialize($blockArray);
	}
}