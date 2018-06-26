<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;
use buildertools\editors\object\BlockList;
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
     * @param BlockList $blocks
     */
    public function addStep(Player $player, BlockList $blocks) {
        #if(empty($this->undoData[$player->getName()])) $this->undoData[$player->getName()] = [];
        $this->undoData[$player->getName()][] = $blocks;
    }

    /**
     * @param Player $player
     */
    public function undo(Player $player) {

        if(!isset($this->undoData[$player->getName()]) || count($this->undoData[$player->getName()]) == 0) {
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

        $this->addRedo($player, $redo);

        array_pop($this->undoData[$player->getName()]);
    }

    private function addRedo(Player $player, BlockList $blocks) {
        if(empty($this->redoData[$player->getName()])) $this->redoData[$player->getName()] = [];
        #array_push($this->redoData[$player->getName()], $blocks);
        $this->redoData[$player->getName()][] = $blocks;
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
            $last = end($this->redoData[$player->getName()]);
        }

        $undo = [];

        /** @var Block $block */
        foreach ($last as $block) {
            $undo[] = $block->getLevel()->getBlock($block->asVector3());
            $block->getLevel()->setBlock($block->asVector3(), $block, true, true);
        }

        $this->addStep($player, $undo);

        array_pop($this->redoData[$player->getName()]);
    }
}