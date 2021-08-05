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
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CenterCommand extends BuilderToolsCommand {

	public function __construct() {
		parent::__construct("/center", "Makes pattern blocks in the middle of selection");
	}

	/** @noinspection PhpUnused */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) return;
		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game!");
			return;
		}

		if(!$this->readPositions($sender, $firstPos, $secondPos)) {
			return;
		}

		$center = $firstPos->add($secondPos)->divide(2);

		$min = $center->floor();
		$max = $center->floor();

		if($center->getX() != $center->getFloorX()) {
			$max->x = $center->getFloorX() + 1;
		}
		if($center->getY() != $center->getFloorY()) {
			$max->y = $center->getFloorY() + 1;
		}
		if($center->getZ() != $center->getFloorZ()) {
			$max->z = $center->getFloorZ() + 1;
		}

		for($x = $min->getFloorX(); $x <= $max->getFloorX(); ++$x) {
			for($y = $min->getFloorY(); $y <= $max->getFloorY(); ++$y) {
				for($z = $min->getFloorZ(); $z <= $max->getFloorZ(); ++$z) {
					$firstPos->getLevelNonNull()->setBlockIdAt($x, $y, $z, BlockLegacyIds::BEDROCK);
					$firstPos->getLevelNonNull()->setBlockDataAt($x, $y, $z, 0);
				}
			}
		}

		$sender->sendMessage(BuilderTools::getPrefix() . "Center of the selection found at {$center->getX()}, {$center->getY()}, {$center->getZ()}");
	}
}