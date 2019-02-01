<?php

/**
 * Copyright (C) 2018-2019  CzechPMDevs
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
use czechpmdevs\buildertools\editors\Editor;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class FlipCommand
 * @package buildertools\commands
 */
class FlipCommand extends BuilderToolsCommand {

    /**
     * FlipCommand constructor.
     */
    public function __construct() {
        parent::__construct("/flip", "Flip selected area", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }

        /** @var Copier $copier */
        $copier = BuilderTools::getEditor(Editor::COPIER);

        if(!isset($copier->copyData[$sender->getName()])) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cUse //copy first!");
        }
        $copier->flip($sender);
    }
}
