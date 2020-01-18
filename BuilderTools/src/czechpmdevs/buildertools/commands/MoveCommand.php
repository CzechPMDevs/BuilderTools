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
use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Filler;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class MoveCommand
 * @package czechpmdevs\buildertools\commands
 */
class MoveCommand extends BuilderToolsCommand {

    /**
     * MoveCommand constructor.
     */
    public function __construct() {
        parent::__construct("/move", "Move selected area", null, []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }

        if(count($args) < 3 || !is_numeric($args[0]) || !is_numeric($args[1]) || !is_numeric($args[2])) {
            $sender->sendMessage("§cUsage: §7//move <x> <y> <z>");
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

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);

        $blocks = BlockList::build($sender->getLevel(), $firstPos, $secondPos);
        $toFill = $blocks->add(new Vector3((int)$args[0], (int)$args[1], (int)$args[2]));

        $toRemove = $filler->prepareFill($firstPos, $secondPos, $firstPos->getLevel(), "air", true);

        $filler->fill($sender, $toRemove);
        $filler->fill($sender, $toFill);

        $sender->sendMessage(BuilderTools::getPrefix() ."§aSelected area were successfully moved.");
    }
}