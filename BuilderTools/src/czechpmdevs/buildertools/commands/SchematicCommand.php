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
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\Schematic;
use czechpmdevs\buildertools\Selectors;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class SchematicCommand
 * @package czechpmdevs\buildertools\commands
 */
class SchematicCommand extends BuilderToolsCommand {

    /**
     * SchematicCommand constructor.
     */
    public function __construct() {
        parent::__construct("/schematic", "Schematics commands", null, ["/schem", "/schematics"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can be used only in game!");
            return;
        }
        if(!isset($args[0]) || !in_array($args[0], ["load", "reload", "list", "paste", "create"])) {
            $sender->sendMessage("§cUsage: §7//schem <reload|load|create|list|paste> [filename]");
            return;
        }
        switch ($args[0]) {
            case "create":
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7//schem <create> <filename>");
                    break;
                }

                if(!Selectors::isSelected(1, $sender)) {
                    $sender->sendMessage(BuilderTools::getPrefix()."§cFirst you need to select the first position.");
                    return;
                }

                if(!Selectors::isSelected(2, $sender)) {
                    $sender->sendMessage(BuilderTools::getPrefix()."§cFirst you need to select the second position.");
                    return;
                }

                $axisVec = Math::calculateAxisVec(Selectors::getPosition($sender, 1), Selectors::getPosition($sender, 2));

                $fileName = stripos($args[1], ".schematic") === false ? $args[1] . ".schematic" : str_replace(".schematic", "", $args[1]) . ".schematic";
                $fileName = $this->getPlugin()->getDataFolder() . "schematics/" . $fileName;

                $schematic = new Schematic(BlockList::build($sender->getLevel(), Selectors::getPosition($sender, 1), Selectors::getPosition($sender, 2)), $axisVec);
                $schematic->save($fileName);

                BuilderTools::getSchematicsManager()->registerSchematic($fileName, $schematic);
                $sender->sendMessage(BuilderTools::getPrefix() . "§aSchematic will be saved as $fileName");
                break;
            case "load":
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7//schem <load> <filename>");
                    break;
                }

                $schematic = BuilderTools::getSchematicsManager()->getSchematic(str_replace(".schematic", "", $args[1]));

                if($schematic === null) {
                    $sender->sendMessage(BuilderTools::getPrefix() . "§cSchematic was not found!");
                    break;
                }

                BuilderTools::getSchematicsManager()->addToPaste($sender, $schematic);
                $sender->sendMessage(BuilderTools::getPrefix() . "§aSchematic copied to clipboard, type §2//schem paste§a to paste!");
                break;
            case "paste":
                BuilderTools::getSchematicsManager()->pasteSchematic($sender);
                break;
            case "list":
                $list = [];
                foreach (BuilderTools::getSchematicsManager()->getLoadedSchematics() as $name => $schematic) {
                    $list[] = $name;
                }
                $sender->sendMessage(BuilderTools::getPrefix() . (string)count($list) . " loaded schematics: " . implode(", ", $list));
                break;
            case "reload":
                BuilderTools::getSchematicsManager()->loadSchematics();
                $sender->sendMessage(BuilderTools::getPrefix() . "§aSchematics reloaded. Type §2//schem list §ato get list of all loaded schematics.");
                break;
        }
    }
}