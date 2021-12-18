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

namespace czechpmdevs\buildertools;

use czechpmdevs\buildertools\utils\IncompatibleConfigException;
use function explode;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

class Configuration {

	public function __construct(
		/** @var array<array-key, mixed> $data */
		private array $data
	) {
	}

	public function getIntProperty(string $key): int {
		if(!is_int($property = $this->getProperty($key))) {
			throw new IncompatibleConfigException("Property '$key' should be int type");
		}
		return $property;
	}

	public function getBoolProperty(string $key): bool {
		if(!is_bool($property = $this->getProperty($key))) {
			throw new IncompatibleConfigException("Property '$key' should be bool type");
		}
		return $property;
	}

	public function getStringProperty(string $key): string {
		if(!is_string($property = $this->getProperty($key))) {
			throw new IncompatibleConfigException("Property '$key' should be string type");
		}
		return $property;
	}

	/**
	 * @throws IncompatibleConfigException
	 */
	public function getProperty(string $key): mixed {
		$property = $this->data;
		foreach(explode(".", $key) as $part) {
			$property = $property[$part] ?? []; // @phpstan-ignore-line
		}

		if(is_array($property)) {
			throw new IncompatibleConfigException("Config option $key is not set");
		}

		return $property;
	}
}