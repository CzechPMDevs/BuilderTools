<?php

declare(strict_types=1);

namespace buildertools\editors;
use buildertools\BuilderTools;
use buildertools\utils\ConfigManager;
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
     * @param Player $player
     */
    public function copy(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Player $player) {
        $this->copyData[$player->getName()] = [
            "data" => [],
            "center" => $player->asPosition(),
            "direction" => $player->getDirection(),
            "rotated" => false
        ];
        $count = 0;
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $this->copyData[$player->getName()]["data"][$count] = [($vec = new Vector3($x, $y, $z))->subtract($player->asVector3()), $player->getLevel()->getBlock($vec)];
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

        /** @var array $undo */
        $undo = [];

        /**
         * @var Vector3 $vec
         * @var Block $block
         */
        foreach ($blocks as [$vec, $block]) {
            if($player->getLevel()->getBlock($vec->add($player->asVector3()))->getId() != $block->getId()) {
                array_push($undo, $player->getLevel()->getBlock($vec->add($player->asVector3())));
            }
            $player->getLevel()->setBlock($vec->add($player->asVector3()), $block, true, true);
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $undo);
    }

    /**
     * @param Player $player
     */
    public function addToRotate(Player $player) {
        if(empty($this->copyData[$player->getName()])) {
            $player->sendMessage(BuilderTools::getPrefix()."§cUse //copy first!");
            return;
        }
        if($this->copyData[$player->getName()]["rotated"] == true) {
            $player->sendMessage(BuilderTools::getPrefix()."§cSelected area is already rotated!");
            return;
        }
        $player->sendMessage(BuilderTools::getPrefix()."Select direction to rotate moving.");
        BuilderTools::getListener()->directionCheck[$player->getName()] = intval($player->getDirection());
    }

    /**
     * @param Player $player
     * @param int $fromDirection
     * @param int $toDirection
     */
    public function rotate(Player $player, int $fromDirection, int $toDirection) {
        $this->copyData[$player->getName()]["rotated"] = true;
        $min = min($fromDirection, $toDirection);
        $max = max($fromDirection, $toDirection);

        if($min == $max) {
            $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated!");
            return;
        }

        $id = "{$fromDirection}:{$toDirection}";

        $undo = [];

        switch ($id) {
            case "0:0":
            case "1:1":
            case "2:2":
            case "3:3":
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "0:1":
            case "1:2":
            case "2:3":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    if(ConfigManager::getSettings($this)["save-undo"]) {
                        $undo[] = $block;
                    }
                    $vec->setComponents($vec->getZ(), $vec->getY(), $vec->getX());
                }
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "0:2":
            case "1:3":
            case "2:0":
            case "3:1":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    if(ConfigManager::getSettings($this)["save-undo"]) {
                        $undo[] = $block;
                    }
                    $vec->setComponents(-$vec->getX(), $vec->getY(), -$vec->getZ());
                }
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "1:0":
            case "2:1":
            case "3:2":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    if(ConfigManager::getSettings($this)["save-undo"]) {
                        $undo[] = $block;
                    }
                    $vec->setComponents(-$vec->getX(), $vec->getY(), -$vec->getZ());
                }
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    if(ConfigManager::getSettings($this)["save-undo"]) {
                        $undo[] = $block;
                    }
                    $vec->setComponents($vec->getZ(), $vec->getY(), $vec->getX());
                }

                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;

            case "3:0":
                /**
                 * @var Vector3 $vec
                 * @var Block $block
                 */
                foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
                    if(ConfigManager::getSettings($this)["save-undo"]) {
                        $undo[] = $block;
                    }
                    $vec->setComponents(-$vec->getX(), $vec->getY(), -$vec->getZ());
                }
                $player->sendMessage(BuilderTools::getPrefix()."§aSelected area rotated! ($id)");
                break;
        }

        if(ConfigManager::getSettings($this)["save-undo"]) {
            /** @var Canceller $canceller */
            $canceller = BuilderTools::getEditor(Editor::CANCELLER);
            $canceller->addStep($player, $undo);
        }
    }

    /**
     * @param Player $player
     */
    public function flip(Player $player) {
        $undo = [];
        /**
         * @var Vector3 $vec
         * @var Block $block
         */
        foreach ($this->copyData[$player->getName()]["data"] as [$vec, $block]) {
            if(ConfigManager::getSettings($this)["save-undo"]) {
                $undo[] = $block;
            }
            $vec->setComponents($vec->getX(), -$vec->getY(), $vec->getZ());
        }

        if(ConfigManager::getSettings($this)["save-undo"]) {
            /** @var Canceller $canceller */
            $canceller = BuilderTools::getEditor(Editor::CANCELLER);
            $canceller->addStep($player, $undo);
        }
        $player->sendMessage(BuilderTools::getPrefix()."§aSelected area flipped!");
    }
}