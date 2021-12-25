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

namespace czechpmdevs\buildertools\async;

use AttachableLogger;
use czechpmdevs\buildertools\BuilderTools;
use pocketmine\scheduler\AsyncTask;
use Throwable;

abstract class BuilderToolsAsyncTask extends AsyncTask {
	private AttachableLogger $logger;

	private string $error = "";

	public function __construct() {
		$this->logger = BuilderTools::getInstance()->getLogger();
	}

	abstract public function execute(): void;

	final public function onRun(): void {
		try {
			$this->execute();
		} catch(Throwable $error) {
			$this->error = $error->getMessage();
		}
	}

	/**
	 * This function is called on main thread before calling
	 * callback function
	 */
	public function complete(): void {
	}

	/** @noinspection PhpUnused */
	final public function onCompletion(): void {
		$this->complete();
		AsyncQueue::callCallback($this);
	}

	protected function getLogger(): AttachableLogger {
		return $this->logger;
	}

	public function getErrorMessage(): ?string {
		return $this->error === "" ? null : $this->error;
	}
}