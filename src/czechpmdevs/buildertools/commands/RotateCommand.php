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

namespace czechpmdevs\buildertools\commands;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Copier;
use pocketmine\command\CommandSender;
use pocketmine\math\Axis;
use pocketmine\player\Player;
use function is_numeric;
use function microtime;
use function round;

class RotateCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/rotate", "Rotate selected area", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(!isset($args[0])) {
			$sender->sendMessage("§cUsage: §7//rotate <yAxis> [xAxis] [zAxis]");
			return;
		}

		foreach($args as $argument) {
			if(!is_numeric($argument)) {
				$sender->sendMessage("§cUsage: §7//rotate <yAxis> [xAxis] [zAxis]");
				return;
			}

			$deg = (int)$argument;
			if($deg > 360 || $deg < 0) {
				$sender->sendMessage(BuilderTools::getPrefix() . "§cPlease, type valid value (0-360 degrees)");
				return;
			}
		}

		$startTime = microtime(true);

		$copier = Copier::getInstance();
		foreach($args as $i => $arg) {
			if($i === 0) {
				$copier->rotate($sender, Axis::Y, (int)$arg);
			} elseif($i === 1) {
				$copier->rotate($sender, Axis::X, (int)$arg);
			} elseif($i === 2) {
				$copier->rotate($sender, Axis::Z, (int)$arg);
			}
		}

		$time = round(microtime(true) - $startTime, 3);
		$sender->sendMessage(BuilderTools::getPrefix() . "§aSelected area rotated (Took $time seconds)!");
	}
}
