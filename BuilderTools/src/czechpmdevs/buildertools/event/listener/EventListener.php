<?php

/**
 * Copyright 2018 CzechPMDevs
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

declare(strict_types=1);

namespace czechpmdevs\buildertools\event\listener;

use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Copier;
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Printer;
use czechpmdevs\buildertools\Selectors;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Player;

/**
 * Class EventListener
 * @package buildertools\events\listener
 */
class EventListener implements Listener {

    /** @var int[] $directionCheck */
    public $directionCheck = [];

    /** @var array $toRotate */
    public $toRotate = [];

    /** @var array $rotateCache */
    public $rotateCache = [];

    /** @var array $wandClicks */
    private $wandClicks = [];

    /** @var array $blockInfoClicks */
    private $blockInfoClicks = [];

    /**
     * @param PlayerMoveEvent $event
     */
    public function onDirectionChange(PlayerMoveEvent $event) {
        if(count($this->directionCheck) == 0) {
            return;
        }

        $player = $event->getPlayer();

        if(!isset($this->directionCheck[$player->getName()])) {
            return;
        }

        $direction = (int)$this->directionCheck[$player->getName()];

        if($direction != $player->getDirection()) {
            if(isset($this->rotateCache[$player->getName()])) {
                if($this->rotateCache[$player->getName()] == $event->getPlayer()->getDirection()) {
                    return;
                }
            }
            $this->rotateCache[$player->getName()] = $player->getDirection();
            $player->sendMessage(BuilderTools::getPrefix()."If you want to rotate object changing direction from {$direction} to {$player->getDirection()} type into the chat §bconfirm§a.");
            $this->toRotate[$player->getName()] = [$player, $direction, $player->getDirection()];
            return;
        }


    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChatConfirm(PlayerChatEvent $event) {
        if(count($this->toRotate) == 0) {
            return;
        }

        $player = $event->getPlayer();
        $msg = explode(" ", strtolower($event->getMessage()))[0];


        if(!isset($this->toRotate[$player->getName()])) {
            return;
        }

        if(!in_array($msg, ["confirm", "cancel"])) {
            $player->sendMessage(BuilderTools::getPrefix()."You are rotating object. If you want to cancel it, type into the chat§b cancel§a.");
            $event->setCancelled(true);
            return;
        }

        if($msg == "cancel") {
            unset($this->toRotate[$player->getName()]);
            unset($this->directionCheck[$player->getName()]);
            $player->sendMessage(BuilderTools::getPrefix()."You are cancelled rotating an object.");
            $event->setCancelled(true);
            return;
        }

        $player->sendMessage(BuilderTools::getPrefix()."§aRotating selected area...");
        $event->setCancelled(true);

        $toRotate = $this->toRotate[$player->getName()];

        unset($this->toRotate[$player->getName()]);
        unset($this->directionCheck[$player->getName()]);

        /** @var Copier $copier */
        $copier = BuilderTools::getEditor(Editor::COPIER);
        $copier->rotate($player, $toRotate[1], $toRotate[2]);

    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onAirClick(PlayerInteractEvent $event) {
        if(!Selectors::isDrawingPlayer($player = $event->getPlayer())) return;
        $position = $player->getTargetBlock(64)->asPosition();
        $printer = BuilderTools::getEditor(Editor::PRINTER);
        if($printer instanceof Printer) {
            $printer->draw($player, $position, $player->getInventory()->getItemInHand()->getBlock(), Selectors::getDrawingPlayerBrush($player), Selectors::getDrawingPlayerMode($player), Selectors::getDrawingPlayerFall($player));
        }
        $event->setCancelled(true);
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event) {
        if(!Selectors::isWandSelector($player = $event->getPlayer())) return;
        Selectors::addSelector($player, 1, $position = new Position(intval($event->getBlock()->getX()), intval($event->getBlock()->getY()), intval($event->getBlock()->getZ()), $player->getLevel()));
        $player->sendMessage(BuilderTools::getPrefix()."§aSelected first position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
        $event->setCancelled(true);
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onBlockTouch(PlayerInteractEvent $event) {
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        if(Selectors::isWandSelector($player = $event->getPlayer())) {
            // antispam ._.
            if(isset($this->wandClicks[$player->getName()]) && microtime(true)-$this->wandClicks[$player->getName()] < 0.5) return;
            $this->wandClicks[$player->getName()] = microtime(true);
            Selectors::addSelector($player, 2, $position = new Position(intval($event->getBlock()->getX()), intval($event->getBlock()->getY()), intval($event->getBlock()->getZ()), $player->getLevel()));
            $player->sendMessage(BuilderTools::getPrefix()."§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
            $event->setCancelled(true);
        }
        if(Selectors::isBlockInfoPlayer($player = $event->getPlayer())) {
            // antispam ._.
            if(isset($this->blockInfoClicks[$player->getName()]) && microtime(true)-$this->blockInfoClicks[$player->getName()] < 0.5) return;
            $this->blockInfoClicks[$player->getName()] = microtime(true);
            $block = $event->getBlock();
            $player->sendTip("§aID: §7" . (string)$block->getId(). ":" . (string)$block->getDamage() . "\n" .
            "§aName: §7" . (string)$block->getName() . "\n" .
            "§aPosition: §7" . (string)$block->getX() . ", " . (string)$block->getY() . ", " . (string)$block->getZ() . "\n" .
            "§aLevel: §7" . $block->getLevel()->getName());
            $event->setCancelled(true);
        }
    }

    /**
     * @return BuilderTools $builderTools
     */
    public function getPlugin():BuilderTools {
        return BuilderTools::getInstance();
    }
}