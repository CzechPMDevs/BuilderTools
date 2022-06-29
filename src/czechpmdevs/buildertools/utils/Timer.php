<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
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

namespace czechpmdevs\buildertools\utils;

use function microtime;
use function round;

class Timer {
	private static int $precision = 3;

	private float $startTime;

	public function __construct() {
		$this->startTime = microtime(true);
	}

	public function time(): float {
		return round(microtime(true) - $this->startTime, self::$precision);
	}

	public static function setPrecision(int $precision): void {
		self::$precision = $precision;
	}
}