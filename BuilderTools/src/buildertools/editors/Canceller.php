<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Server;


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

        if(count($this->undoData[$player->getName()]) == 0) {
            $player->sendMessage(BuilderTools::getPrefix()."§cThere are not actions to undo!");
            return;
        }

        if(count($this->undoData[$player->getName()]) == 1) {
            $last = $this->undoData[$player->getName()][0];
        }
        else {
            $last = end($this->undoData[$player->getName()]);
        }


        /** @var Block $block */
        foreach ($last as $block) {
            $block->getLevel()->setBlock($block->asVector3(), $block, true, true);
        }

        $index = intval(count($this->undoData[$player->getName()])-1);

        $this->redoData[$player->getName()][] = $this->undoData[$player->getName()][$index];
        unset($this->undoData[$player->getName()][$index]);
    }

    public function redo(Player $player) {
        if(count($this->redoData[$player->getName()]) == 0) {
            $player->sendMessage(BuilderTools::getPrefix()."§cThere are not actions to undo!");
            return;
        }

        if(count($this->redoData) == 1) {
            $last = $this->redoData[0];
        }
        else {
            $last = end($this->redoData);
        }


        /** @var Block $block */
        foreach ($last as $block) {
            $block->getLevel()->setBlock($block->asVector3(), $block, true, true);
        }

        $index = intval(count($this->undoData[$player->getName()])-1);

        $this->redoData[$player->getName()][] = $this->undoData[$player->getName()][$index];
        unset($this->undoData[$player->getName()][$index]);
    }
}