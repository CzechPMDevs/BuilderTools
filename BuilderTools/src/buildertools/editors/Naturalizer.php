<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;
use buildertools\event\NaturalizeEvent;
use buildertools\utils\ConfigManager;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Naturalizer
 * @package buildertools\editors
 */
class Naturalizer extends Editor {

    /** @var array $undo */
    protected $undo = [];

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     * @param Level $level
     * @param Player $player
     */
    public function naturalize(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Level $level, Player $player) {
        $settings = ConfigManager::getSettings($this);

        $event = new NaturalizeEvent($player, $level, new Vector3($x1, $y1, $z1), new Vector3($x2, $y2, $z2), $settings);
        $this->getPlugin()->getServer()->getPluginManager()->callEvent($event);
        if($event->isCancelled()) return;

        $settings = $event->getSettings();

        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                $this->fix(new Vector3($x, max($y1, $y2), $z), $level, min($y1, $y2), (bool)$settings["force-fill"], (bool)$settings["save-undo"]);
            }
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $this->undo);
        $this->undo = [];
    }

    private function fix(Vector3 $vector3, Level $level, int $minY, bool $force, bool $undo) {
        start:
        if($vector3->getX() > 1 && $level->getBlock($vector3)->getId() == Block::AIR) {
            $vector3 = $vector3->subtract(0, 1, 0);
            goto start;
        }

        if($vector3->getX() < 0) {
            return;
        }

        if($undo) $this->undo[] = $level->getBlock($vector3);
        $level->setBlock($vector3, Block::get(Block::GRASS), $force);


        $r = rand(3, 4);

        for($y = 1; $y < $r; $y++) {
            if($undo) $this->undo[] = $level->getBlock($vector3->add(0, -$y, 0));
            $level->setBlock($vector3->add(0, -$y, 0), Block::get(Block::DIRT), $force);
        }

        for($y = $vector3->getY()-$r; $y >= $minY; $y--) {
            if($undo) $this->undo[] = $level->getBlock(new Vector3($vector3->getX(), $y, $vector3->getZ()));
            $level->setBlock(new Vector3($vector3->getX(), $y, $vector3->getZ()), Block::get(Block::STONE), $force);
        }
    }


    /**
     * @return string
     */
    public function getName(): string {
        return "Naturalizer";
    }
}