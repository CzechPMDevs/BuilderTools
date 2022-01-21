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

namespace czechpmdevs\buildertools\session;

use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\session\selection\CuboidSelection;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;

class Session {
	private SelectionHolder $selectionHolder;
	private ClipboardHolder $clipboardHolder;
	private ReverseDataHolder $reverseDataHolder;

	/**
	 * @var ?array{0: int, 1: int, 2: bool} $drawData
	 * @deprecated
	 */
	private ?array $drawData = null;

	private ?BlockIdentifierList $mask = null;

	public function __construct(
		private Player $player,
	) {
		$this->selectionHolder = new CuboidSelection($this);
		$this->clipboardHolder = new ClipboardHolder($this);
		$this->reverseDataHolder = new ReverseDataHolder($this);
	}

	public function setSelectionHolder(SelectionHolder $holder): void {
		$this->selectionHolder = $holder;
	}

	public function getSelectionHolder(): SelectionHolder {
		return $this->selectionHolder;
	}

	public function getClipboardHolder(): ClipboardHolder {
		return $this->clipboardHolder;
	}

	public function getReverseDataHolder(): ReverseDataHolder {
		return $this->reverseDataHolder;
	}

	public function setMask(?BlockIdentifierList $mask): void {
		$this->mask = $mask;
	}

	public function getMask(): ?BlockIdentifierList {
		return $this->mask;
	}

	public function startDrawing(int $brush, int $mode, bool $fall): void {
		$this->drawData = [$brush, $mode, $fall];
	}

	public function stopDrawing(): void {
		$this->drawData = null;
	}

	public function isDrawing(): bool {
		return $this->drawData !== null;
	}

	public function getDrawBrush(): int {
		if($this->drawData === null) {
			throw new AssumptionFailedError("Draw data is null");
		}

		return $this->drawData[0];
	}

	public function getDrawMode(): int {
		if($this->drawData === null) {
			throw new AssumptionFailedError("Draw data is null");
		}

		return $this->drawData[1];
	}

	public function getDrawFall(): bool {
		if($this->drawData === null) {
			throw new AssumptionFailedError("Draw data is null");
		}

		return $this->drawData[2];
	}

	public function getPlayer(): Player {
		return $this->player;
	}
}