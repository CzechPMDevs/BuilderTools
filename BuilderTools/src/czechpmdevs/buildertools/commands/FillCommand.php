<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Filler;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class FillCommand
 * @package buildertools\commands
 */
class FillCommand extends BuilderToolsCommand {

    /**
     * FillCommand constructor.
     */
    public function __construct() {
        parent::__construct("/fill", "Fill selected positions.", null, ["/set", "/change"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cUsage: §7//fill <id1:meta1,id2:meta2,...>");
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

        $firstPos = Selectors::getPosition($sender, 1);
        $secondPos = Selectors::getPosition($sender, 2);

        if($firstPos->getLevel()->getName() != $secondPos->getLevel()->getName()) {
            $sender->sendMessage(BuilderTools::getPrefix()."§cPositions must be in same level");
            return;
        }

        $startTime = microtime(true);

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);

        $blocks = $filler->prepareFill($firstPos->asVector3(), $secondPos->asVector3(), $firstPos->getLevel(), $args[0]);
        $result = $filler->fill($sender, $blocks);

        $sender->sendMessage(BuilderTools::getPrefix()."§aSelected area filled in " . (string)round(microtime(true)-$startTime, 2) . " (" . (string)$result->countBlocks . " block changed)");
    }
}