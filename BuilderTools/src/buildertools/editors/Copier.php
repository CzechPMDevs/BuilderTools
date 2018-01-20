<?php

declare(strict_types=1);

namespace buildertools\editors;
use buildertools\BuilderTools;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Copier
 * @package buildertools\editors
 */
class Copier extends Editor {

    /** @var array $copyData */
    public $copyData = [];

    /**
     * @return string $copier
     */
    public function getName(): string {
        return "Copier";
    }

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     */
    public function copy(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Player $player) {
        $this->copyData[$player->getName()] = ["data" => [], "center" => $player->asPosition()];
        $count = 0;
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $this->copyData[$player->getName()]["data"][$count] = [$player->asVector3()->add($vec = new Vector3($x, $y, $z)), $player->getLevel()->getBlock($vec)];
                    $count++;
                }
            }
        }
        $player->sendMessage(BuilderTools::getPrefix()."§a{$count} blocks copied to clipboard! Use //paste to paste");
    }

    /**
     * @param Player $player
     */
    public function paste(Player $player) {
        if(empty($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix()."§cUse //copy first!");
            return;
        }

        /** @var array $blocks */
        $blocks = $this->copyData[$player->getName()]["data"];

        /** @var Position $center $center */
        $center = $this->copyData[$player->getName()]["center"];

        /**
         * @var Vector3 $vec
         * @var Block $block
         */
        foreach ($blocks as [$vec, $block]) {
            $player->getLevel()->setBlock($vec->subtract($center), $block, true, true);
        }
    }

    public function flip() {

    }
}