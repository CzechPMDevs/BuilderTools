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

namespace czechpmdevs\buildertools\editors\object;

use function round;

final class UpdateResult {

	protected int $blocksChanged = 0;
	protected float $processTime = 0.0;

	protected ?string $errorMessage = null;

	protected function __construct() {
	}

	public function getBlocksChanged(): int {
		return $this->blocksChanged;
	}

	public function getProcessTime(): float {
		return $this->processTime;
	}

	/** @deprecated */
	public function successful(): bool {
		return $this->errorMessage === null;
	}

	/** @deprecated */
	public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}

	public static function success(int $blocksChanged, float $processTime): UpdateResult {
		$result = new UpdateResult();
		$result->blocksChanged = $blocksChanged;
		$result->processTime = round($processTime, 3);

		return $result;
	}

	/** @deprecated */
	public static function error(string $message): UpdateResult {
		$result = new UpdateResult();
		$result->errorMessage = $message;

		return $result;
	}
}