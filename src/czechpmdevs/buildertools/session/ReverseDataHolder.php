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

namespace czechpmdevs\buildertools\session;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use function array_pop;

class ReverseDataHolder {
	/** @var BlockArray[] */
	private array $undoData = [];
	/** @var BlockArray[] */
	private array $redoData = [];

	public function __construct(
		private Session $session
	) {}

	/**
	 * Removes and returns last step added to undo
	 */
	public function nextUndoAction(): ?BlockArray {
		return array_pop($this->undoData);
	}

	public function saveUndo(BlockArray $blockArray): void {
		$this->undoData[] = $blockArray;
	}

	/**
	 * Removes and returns last step added to redo
	 */
	public function nextRedoAction(): ?BlockArray {
		return array_pop($this->redoData);
	}

	public function saveRedo(BlockArray $blockArray): void {
		$this->redoData[] = $blockArray;
	}

	protected function getSession(): Session {
		return $this->session;
	}
}