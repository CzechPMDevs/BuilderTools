<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Server;


/**
 * Class Canceller
 * @package buildertools\editors
 */
class Canceller extends Editor {

    /** @var array $undoData */
    public $undoData = [];

    /** @var array $redoData */
    public $redoData = [];

    /**
     * @return string $name
     */
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

        if(empty($this->undoData[$player->getName()]) || count($this->undoData[$player->getName()]) == 0) {
            $player->sendMessage(BuilderTools::getPrefix()."§cThere are not actions to undo!");
            return;
        }

        if(count($this->undoData[$player->getName()]) == 1) {
            $last = $this->undoData[$player->getName()][0];
        }
        else {
            $last = end($this->undoData[$player->getName()]);
        }

        $redo = [];

        /** @var Block $block */
        foreach ($last as $block) {
            $redo[] = $block->getLevel()->getBlock($block->asVector3());
            $block->getLevel()->setBlock($block->asVector3(), $block, true, true);
        }

        $index = intval(count($this->undoData[$player->getName()])-1);

        $this->addRedo($player, $redo);

        unset($this->undoData[$player->getName()][$index]);
    }

    private function addRedo(Player $player, $blocks) {
        if(empty($this->redoData[$player->getName()])) $this->redoData[$player->getName()] = [];
        array_push($this->redoData[$player->getName()], $blocks);
    }

    public function redo(Player $player) {
        if(empty($this->redoData[$player->getName()]) || count($this->redoData[$player->getName()]) == 0) {
            $player->sendMessage(BuilderTools::getPrefix()."§cThere are not actions to redo!");
            return;
        }

        if(count($this->redoData) == 1) {
            $last = $this->redoData[$player->getName()][0];
        }
        else {
            $last = end($this->redoData);
        }

        $undo = [];

        /** @var Block $block */
        foreach ($last as $block) {
            $undo[] = $block->getLevel()->getBlock($block->asVector3());
            $block->getLevel()->setBlock($block->asVector3(), $block, true, true);
        }

        $index = intval(count($this->redoData[$player->getName()])-1);
        $this->addStep($player, $undo);

        unset($this->redoData[$player->getName()][$index]);
    }
}