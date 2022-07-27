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

use czechpmdevs\buildertools\blockstorage\BlockStorageHolder;
use czechpmdevs\buildertools\blockstorage\Clipboard;
use czechpmdevs\buildertools\blockstorage\helpers\BlockArrayIteratorHelper;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\editors\object\FillSession;
use czechpmdevs\buildertools\editors\object\MaskedFillSession;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use czechpmdevs\buildertools\utils\Timer;
use pocketmine\world\Position;
use RuntimeException;

class ClipboardHolder {
	private ?Clipboard $clipboard = null;

	public function __construct(
		private Session $session
	) {
	}

	public function setClipboard(?Clipboard $clipboard): void {
		$this->clipboard = $clipboard;
	}

	public function getClipboard(): ?Clipboard {
		return $this->clipboard;
	}

	public function paste(Position $position, ?BlockIdentifierList $mask = null): UpdateResult {
		if($this->clipboard === null) {
			throw new RuntimeException("There is not clipboard copied");
		}

		$timer = new Timer();

		$relativePosition = $this->clipboard->getRelativePosition();

		if($mask === null) {
			$fillSession = new FillSession($position->getWorld(), true, true);
		} else {
			$fillSession = new MaskedFillSession($position->getWorld(), true, true, $mask);
		}

		$motion = $position->add(0.5, 0, 0.5)->subtractVector($relativePosition);

		$floorX = $motion->getFloorX();
		$floorY = $motion->getFloorY();
		$floorZ = $motion->getFloorZ();

		$iterator = new BlockArrayIteratorHelper($this->clipboard->getBlockStorage());
		while($iterator->hasNext()) {
			$iterator->readNext($x, $y, $z, $fullStateId);
			$fillSession->setBlockAt($floorX + $x, $floorY + $y, $floorZ + $z, $fullStateId);
		}

		$fillSession->reloadChunks($position->getWorld());
		$fillSession->close();

		$changes = $fillSession->getChanges();

		$this->session->getReverseDataHolder()->saveUndo(new BlockStorageHolder($changes, $position->getWorld()));

		return UpdateResult::success($fillSession->getBlocksChanged(), $timer->time());
	}

	protected function getSession(): Session {
		return $this->session;
	}
}