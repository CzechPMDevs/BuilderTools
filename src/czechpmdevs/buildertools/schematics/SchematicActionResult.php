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

namespace czechpmdevs\buildertools\schematics;

use function round;

class SchematicActionResult {

	protected bool $loaded = true;

	protected string $errorMessage = "";

	protected float $processTime = 0;

	private function __construct() { }

	public function successful(): bool {
		return $this->loaded;
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}

	public function getProcessTime(): float {
		return $this->processTime;
	}

	public static function success(float $processTime): SchematicActionResult {
		$result = new SchematicActionResult();
		$result->processTime = round($processTime, 3);

		return $result;
	}

	public static function error(string $errorMessage): SchematicActionResult {
		$result = new SchematicActionResult();
		$result->loaded = false;
		$result->errorMessage = $errorMessage;

		return $result;
	}
}