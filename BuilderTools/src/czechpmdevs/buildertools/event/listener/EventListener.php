<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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
use czechpmdevs\buildertools\editors\Editor;
use czechpmdevs\buildertools\editors\Printer;
use czechpmdevs\buildertools\Selectors;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;

/**
 * Class EventListener
 * @package buildertools\events\listener
 */
class EventListener implements Listener {

    /** @var array $wandClicks */
    private $wandClicks = [];

    /** @var array $blockInfoClicks */
    private $blockInfoClicks = [];

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
        if(Selectors::isWandSelector($player = $event->getPlayer()) || ($event->getItem()->getId() == Item::WOODEN_AXE && $event->getItem()->hasEnchantment(50))) {
            Selectors::addSelector($player, 1, $position = new Position((int)($event->getBlock()->getX()), (int)($event->getBlock()->getY()), (int)($event->getBlock()->getZ()), $player->getLevel()));
            $player->sendMessage(BuilderTools::getPrefix()."§aSelected first position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onBlockTouch(PlayerInteractEvent $event) {
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        if(Selectors::isWandSelector($player = $event->getPlayer()) || ($event->getItem()->getId() == Item::WOODEN_AXE && $event->getItem()->hasEnchantment(50))) {
            // antispam ._.
            if(isset($this->wandClicks[$player->getName()]) && microtime(true)-$this->wandClicks[$player->getName()] < 0.5) return;
            $this->wandClicks[$player->getName()] = microtime(true);
            Selectors::addSelector($player, 2, $position = new Position((int)($event->getBlock()->getX()), (int)($event->getBlock()->getY()), (int)($event->getBlock()->getZ()), $player->getLevel()));
            $player->sendMessage(BuilderTools::getPrefix()."§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
            $event->setCancelled(true);
        }
        if(Selectors::isBlockInfoPlayer($player = $event->getPlayer()) || ($event->getItem()->getId() == Item::STICK && $event->getItem()->hasEnchantment(50))) {
            // antispam ._.
            if(isset($this->blockInfoClicks[$player->getName()]) && microtime(true)-$this->blockInfoClicks[$player->getName()] < 0.5) return;
            $this->blockInfoClicks[$player->getName()] = microtime(true);
            $block = $event->getBlock();
            $player->sendTip("§aID: §7" . (string)$block->getId(). ":" . (string)$block->getDamage() . "\n" .
            "§aName: §7" . (string)$block->getName() . "\n" .
            "§aPosition: §7" . (string)$block->getX() . ", " . (string)$block->getY() . ", " . (string)$block->getZ() . "\n" .
            "§aLevel: §7" . $block->getLevel()->getName());
        }
    }

    /**
     * @return BuilderTools
     */
    public function getPlugin(): BuilderTools {
        return BuilderTools::getInstance();
    }
}