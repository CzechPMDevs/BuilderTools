<?php

declare(strict_types=1);

namespace buildertools\event\listener;

use buildertools\BuilderTools;
use buildertools\editors\Printer;
use buildertools\Selectors;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;

/**
 * Class EventListener
 * @package buildertools\events\listener
 */
class EventListener implements Listener {

    public function onAirClick(PlayerInteractEvent $event) {
        if(!Selectors::isDrawingPlayer($player = $event->getPlayer())) return;
        $position = $player->getTargetBlock(20)->asPosition();
        $printer = BuilderTools::getEditor("Printer");
        if($printer instanceof Printer) {
            $printer->draw($position, Selectors::getDrawingPlayerBrush($player), $player->getInventory()->getItemInHand()->getBlock(), Selectors::getDrawingPlayerMode($player));
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
        if(!Selectors::isWandSelector($player = $event->getPlayer())) return;
        Selectors::addSelector($player, 2, $position = new Position(intval($event->getBlock()->getX()), intval($event->getBlock()->getY()), intval($event->getBlock()->getZ()), $player->getLevel()));
        $player->sendMessage(BuilderTools::getPrefix()."§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}");
        $event->setCancelled(true);
    }

    /**
     * @return BuilderTools $builderTools
     */
    public function getPlugin():BuilderTools {
        return BuilderTools::getInstance();
    }
}