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

namespace czechpmdevs\buildertools\editors;

use czechpmdevs\buildertools\blockstorage\async\ThreadSafeBlock;
use czechpmdevs\buildertools\blockstorage\async\ThreadSafeBlockList;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\blockstorage\BlockList;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
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

    /**
     * @var array $blocks
     */
    private const BLOCK_FIX_DATA = [
        158 => [BlockIds::WOODEN_SLAB, 0],
        125 => [BlockIds::DOUBLE_WOODEN_SLAB, ""],
        188 => [BlockIds::FENCE, 0],
        189 => [BlockIds::FENCE, 1],
        190 => [BlockIds::FENCE, 2],
        191 => [BlockIds::FENCE, 3],
        192 => [BlockIds::FENCE, 4],
        193 => [BlockIds::FENCE, 5],
        166 => [BlockIds::INVISIBLE_BEDROCK, 0],
        208 => [BlockIds::GRASS_PATH, 0],
        198 => [BlockIds::END_ROD, 0],
        126 => [BlockIds::WOODEN_SLAB, ""],
        95  => [BlockIds::STAINED_GLASS, ""],
        199 => [BlockIds::CHORUS_PLANT, 0],
        202 => [BlockIds::PURPUR_BLOCK, 0],
        251 => [BlockIds::CONCRETE, 0],
        204 => [BlockIds::PURPUR_BLOCK, 0]
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
            if(isset(self::BLOCK_FIX_DATA[$id])) {
                if(is_int(self::BLOCK_FIX_DATA[$id][1])) $damage = self::BLOCK_FIX_DATA[$id][1];
                $id = self::BLOCK_FIX_DATA[$id][0];
            }

            if($block->getId() == BlockIds::TRAPDOOR || $block->getId() == BlockIds::IRON_TRAPDOOR) {
                $block->setDamage($this->fixTrapdoorMeta($block->getDamage()));
            }
            if($block->getId() == BlockIds::STONE_BUTTON || $block->getId() == BlockIds::STONE_BUTTON) {
                $block->setDamage($this->fixButtonMeta($block->getDamage()));
            }

            $block = Block::get($id, $damage);
            $block->setComponents($block->getX(), $block->getY(), $block->getZ());
            $newList->addBlock($block->asVector3(), $block);
        }
        return $newList;
    }

    /**
     * @param ThreadSafeBlockList $blockList
     * @return ThreadSafeBlockList
     */
    public function fixThreadSafeBlockList(ThreadSafeBlockList $blockList): ThreadSafeBlockList {
        /** @var ThreadSafeBlock $block */
        foreach ($blockList as $index => $block) {
            if(isset(self::BLOCK_FIX_DATA[$block->getId()])) {
                if(is_int(self::BLOCK_FIX_DATA[$block->getId()][1])) {
                    $block->setDamage(self::BLOCK_FIX_DATA[$block->getId()][1]);
                }

                $block->setId(self::BLOCK_FIX_DATA[$block->getId()][0]);
            }

            if($block->getId() == BlockIds::TRAPDOOR || $block->getId() == BlockIds::IRON_TRAPDOOR) {
                $block->setDamage($this->fixTrapdoorMeta($block->getDamage()));
            }
            if($block->getId() == BlockIds::STONE_BUTTON || $block->getId() == BlockIds::STONE_BUTTON) {
                $block->setDamage($this->fixButtonMeta($block->getDamage()));
            }

            $blockList[$index] = $block;
        }

        return $blockList;
    }

    /**
     * @param int $meta
     * @return int
     */
    private function fixButtonMeta(int $meta): int {
        return (6 - $meta) % 6;
    }

    /**
     * @param int $meta
     * @return int
     */
    private function fixTrapdoorMeta(int $meta): int {
        $key = $meta >> 2;
        if($key == 0) {
            return 3 - $meta;
        } elseif($key == 3) {
            return 27 - $meta;
        } else {
            return 15 - $meta;
        }
    }

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     * @param Level $level
     * @param Player $player
     * @param bool $replaceHeads
     * @param bool $fixTiles
     */
    public function fix(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Level $level, Player $player, bool $replaceHeads = false, bool $fixTiles = true) {
        $blocks = self::BLOCK_FIX_DATA;

        if($replaceHeads)
            $blocks[Block::MOB_HEAD_BLOCK] = [Block::AIR, 0];

        $blockList = new BlockList();
        $blockList->setLevel($level);

        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    #$id = $level->getBlockIdAt($x, $y, $z);
                    $id = $level->getBlockAt($x, $y, $z)->getId();

                    if($fixTiles) {
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
