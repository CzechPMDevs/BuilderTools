<?php

declare(strict_types=1);

namespace buildertools\editors;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\math\Vector3;


/**
 * Class Canceller
 * @package buildertools\editors
 */
class Canceller extends Editor {

    public $undoData = [];

    public $redoData = [];

    public function getName(): string {
        return "Canceller";
    }

    /**
     * @param Player $player
     * @param array $blocks
     */
    public function addStep(Player $player, $blocks) {
        if(empty($this->undoData[$player->getName()])) $this->undoData[$player->getName()] = [];
        array_push($this->undoData[$player->getName()], $blocks);
    }

    /**
     * @param Player $player
     */
    public function undo(Player $player) {
        $last = end($this->undoData);

        /** @var Block $block */
        foreach ($last as $block) {
            $block->getLevel()->setBlock($block->asVector3(), $block, true, true);
        }

        $index = intval(count($this->undoData[$player->getName()])-1);

        $this->redoData[$player->getName()][] = $this->undoData[$player->getName()][$index];
        unset($this->undoData[$player->getName()][$index]);
    }

    public function redo(Player $player) {

    }
}