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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\utils\WorldFixUtil;
use pocketmine\command\CommandSender;

class FixCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/fix", "Fixes world");
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;

		if(!isset($args[0])) {
			$sender->sendMessage("§cUsage: §7//fix <world>");
			return;
		}

		WorldFixUtil::fixWorld($sender, $args[0]);
	}
}