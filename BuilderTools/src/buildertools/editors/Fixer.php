<?php

namespace buildertools\editors;

use buildertools\BuilderTools;
use buildertools\event\FixEvent;
use buildertools\utils\ConfigManager;
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
        #144 => [Block::AIR, 0], // mob heads
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
        $settings = ConfigManager::getSettings($this);

        $event = new FixEvent($player, $level, new Vector3($x1, $y1, $z1), new Vector3($x2, $y2, $z2), $settings);
        $this->getPlugin()->getServer()->getPluginManager()->callEvent($event);

        if($event->isCancelled()) return;

        $settings = $event->getSettings();
        $blocks = array_merge(self::$blocks, (array)$settings["added-blocks"]);
        if($settings["remove-heads"]) $blocks = array_merge($blocks, [144 => [Block::AIR, 0]]);

        $count = 0;
        $undo = [];
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $id = $level->getBlock(new Vector3($x, $y, $z))->getId();
                    if(isset($blocks[$id])) {
                        if($settings["save-undo"]) $undo[] = $level->getBlock(new Vector3($x, $y, $z));
                        $level->setBlockIdAt($x, $y, $z, $blocks[$id][0]);
                        if(is_int(self::$blocks[$id][1])) $level->setBlockDataAt($x, $y, $z, $blocks[$id][1]);
                        $count++;
                    }

                    if($settings["fix-tiles"]) {
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
                }
            }
        }
        $player->sendMessage(BuilderTools::getPrefix()."Selected area successfully fixed! ($count blocks changed!)");
    }

    public function getName(): string {
        return "Fixer";
    }
}
