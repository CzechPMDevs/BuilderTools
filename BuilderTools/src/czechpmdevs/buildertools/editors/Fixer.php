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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\blockstorage\BlockList;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Banner;
use pocketmine\tile\Bed;
use pocketmine\tile\Chest;
use pocketmine\tile\Sign;

/**
 * Class Fixer
 * @package buildertools\editors
 */
class Fixer extends Editor {

    const REMOVE_HEADS = false;
    const FIX_TILES = true;

    /**
     * @var array $blocks
     */
    private static $blocks = [
        158 => [Block::WOODEN_SLAB, 0],
        125 => [Block::DOUBLE_WOODEN_SLAB, ""],
        188 => [Block::FENCE, 0],
        189 => [Block::FENCE, 1],
        190 => [Block::FENCE, 2],
        191 => [Block::FENCE, 3],
        192 => [Block::FENCE, 4],
        193 => [Block::FENCE, 5],
        166 => [Block::INVISIBLE_BEDROCK, 0],
        208 => [Block::GRASS_PATH, 0],
        198 => [Block::END_ROD, 0],
        126 => [Block::WOODEN_SLAB, ""],
        95 => [Block::STAINED_GLASS, ""],
        199 => [Block::CHORUS_PLANT, 0],
        202 => [Block::PURPUR_BLOCK, 0],
        251 => [Block::CONCRETE, 0],
        204 => [Block::PURPUR_BLOCK, 0]
    ];

    /**
     * @param BlockList $blockList
     * @return BlockList
     */
    public function fixBlockList(BlockList $blockList): BlockList {
        $newList = new BlockList();
        foreach ($blockList->getAll() as $block) {
            $id = $block->getId();
            $damage = $block->getDamage();
            $x = $block->getX();
            $y = $block->getY();
            $z = $block->getZ();
            if(isset(self::$blocks[$id])) {
                if(is_int(self::$blocks[$id][1])) $damage = self::$blocks[$id][1];
                $id = self::$blocks[$id][0];
            }

            $block = Block::get($id, $damage);
            $block->setComponents($x, $y, $z);
            $newList->addBlock($block->asVector3(), $block);
        }
        return $newList;
    }

    /**
     * @param $x1
     * @param $y1
     * @param $z1
     * @param $x2
     * @param $y2
     * @param $z2
     * @param Level $level
     * @param Player $player
     */
    public function fix($x1, $y1, $z1, $x2, $y2, $z2, Level $level, Player $player) {
        $blocks = self::$blocks;

        if(self::REMOVE_HEADS) $blocks[Block::MOB_HEAD_BLOCK] = [Block::AIR, 0];

        $blockList = new BlockList();
        $blockList->setLevel($level);

        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    #$id = $level->getBlockIdAt($x, $y, $z);
                    $id = $level->getBlockAt($x, $y, $z)->getId();

                    if(self::FIX_TILES && \pocketmine\BASE_VERSION != "4.0.0") {
                        switch ($id) {
                            case Block::CHEST:
                                if($level->getTile(new Vector3($x, $y, $z)) === null)
                                    $level->addTile(new Chest($level, Chest::createNBT(new Vector3($x, $y, $z))));
                                break;
                            case Block::SIGN_POST:
                            case Block::WALL_SIGN:
                                if($level->getTile(new Vector3($x, $y, $z)) === null)
                                    $level->addTile(new Sign($level, Sign::createNBT(new Vector3($x, $y, $z))));
                                break;
                            case Block::BED_BLOCK:
                                if($level->getTile(new Vector3($x, $y, $z)) === null)
                                    $level->addTile(new Bed($level, Bed::createNBT(new Vector3($x, $y, $z))));
                                break;
                            case Block::STANDING_BANNER:
                            case Block::WALL_BANNER:
                                if($level->getTile(new Vector3($x, $y, $z)) === null)
                                    $level->addTile(new Banner($level, Banner::createNBT(new Vector3($x, $y, $z))));
                                break;
                        }
                    }


                    if(isset($blocks[$id])) $blockList->addBlock(new Vector3($x, $y, $z), Block::get($blocks[$id][0], (is_int($blocks[$id][1]) ? $blocks[$id][1] : $level->getBlockAt($x, $y, $z)->getDamage())));
                }
            }
        }

        /** @var Filler $filler */
        $filler = BuilderTools::getEditor(Editor::FILLER);
        $result = $filler->fill($player, $blockList);

        $player->sendMessage(BuilderTools::getPrefix()."Selected area successfully fixed! (".(string)($result->countBlocks)." blocks changed!)");
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Fixer";
    }
}
