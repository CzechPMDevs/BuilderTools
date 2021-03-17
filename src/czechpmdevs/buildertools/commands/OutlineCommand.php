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
use czechpmdevs\buildertools\editors\Filler;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;

class OutlineCommand extends BuilderToolsCommand {

    public function __construct() {
        parent::__construct("/outline", "Fills hollow selected area.", null, ["/hset"]);
    }

    /** @noinspection PhpUnused */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cUsage: §7//outline <id1:meta1,id2:meta2,...>");
            return;
        }

        if(!Selectors::isSelected(1, $sender)) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cFirst you need to select the first position.");
            return;
        }

        if(!Selectors::isSelected(2, $sender)) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cFirst you need to select the second position.");
            return;
        }

        /** @var Position $firstPos */
        $firstPos = Selectors::getPosition($sender, 1);
        /** @var Position $secondPos */
        $secondPos = Selectors::getPosition($sender, 2);

        if($firstPos->getLevelNonNull()->getName() != $secondPos->getLevelNonNull()->getName()) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cPositions must be in same level");
            return;
        }

        $startTime = microtime(true);

        $filler = Filler::getInstance();
        $blocks = $filler->prepareFill($firstPos->asVector3(), $secondPos->asVector3(), $firstPos->getLevelNonNull(), $args[0], false);
        $result = $filler->fill($sender, $blocks);

        $time = round(microtime(true) - $startTime, 3);

        $sender->sendMessage(BuilderTools::getPrefix()."Filled, §a{$result->getBlocksChanged()} changed (Took $time seconds)");
    }
}