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

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use function array_key_exists;

class SessionManager {
	use SingletonTrait;

	/** @var array<string, Session> */
	private array $sessions = [];

	private function loadSession(Player $player): Session {
		return $this->sessions[$player->getName()] = new Session($player);
	}

	public function getSession(Player $player): Session {
		return $this->sessions[$player->getName()] ?? $this->loadSession($player);
	}

	public function closeSession(Player $player): void {
		if(!array_key_exists($player->getName(), $this->sessions)) {
			return;
		}

		unset($this->sessions[$player->getName()]);
	}
}