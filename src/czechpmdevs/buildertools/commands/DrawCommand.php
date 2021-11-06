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

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Printer;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function in_array;
use function is_numeric;
use function strval;

class DrawCommand extends BuilderToolsCommand {

	private int $minBrush = 1;

	private int $maxBrush = 10;

	public function __construct() {
		parent::__construct("/draw", "Draw witch blocks", null, []);
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}
		if(!isset($args[0])) {
			$sender->sendMessage("§cUsage: §7//draw <cube|sphere|off> [brush: $this->minBrush-$this->maxBrush | on | off]  [fall = false]");
			return;
		}
		if(!in_array(strval($args[0]), ["on", "off", "cube", "sphere", "cylinder", "hcube", "hsphere", "hcylinder"], true)) {
			$sender->sendMessage("§cUsage: §7//draw <cube|sphere|cylinder|hcube|hsphere|hcylinder|off> [brush: $this->minBrush-$this->maxBrush]  [fall = false]");
			return;
		}
		if(isset($args[1]) && (!is_numeric($args[1]) || ((int)($args[1]) > $this->maxBrush || (int)($args[1]) < $this->minBrush))) {
			$sender->sendMessage("§cBrush #$args[1] wasn't found!");
			return;
		}
		if($args[0] == "off") {
			Selectors::removeDrawingPlayer($sender);
			$sender->sendMessage(BuilderTools::getPrefix() . "§aBrush removed!");
			return;
		}

		$mode = 0;

		switch($args[0]) {
			case "cube":
				$mode = Printer::CUBE;
				break;
			case "sphere":
				$mode = Printer::SPHERE;
				break;
			case "cylinder":
				$mode = Printer::CYLINDER;
				break;
			case "hcube":
				$mode = Printer::HOLLOW_CUBE;
				break;
			case "hsphere":
				$mode = Printer::HOLLOW_SPHERE;
				break;
			case "hcylinder":
				$mode = Printer::HOLLOW_CYLINDER;
				break;
		}

		$brush = 1;
		if(isset($args[1]) && is_numeric($args[1])) {
			$brush = (int)($args[1]);
		}

		$fall = false;
		if(isset($args[2]) && $args[2] == "true") {
			$fall = true;
		}

		Selectors::addDrawingPlayer($sender, $brush, $mode, $fall);

		$fall = $fall ? "§2true§a" : "§cfalse§a";
		$sender->sendMessage(BuilderTools::getPrefix() . "§aSelected brush §7#$brush §a(§7shape: §a$args[0] §7Fall:$fall)!");
	}
}