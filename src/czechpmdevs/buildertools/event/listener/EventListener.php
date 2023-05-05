<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
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
use czechpmdevs\buildertools\editors\Printer;
use czechpmdevs\buildertools\item\WoodenAxe;
use czechpmdevs\buildertools\session\SessionManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use RuntimeException;
use function array_key_exists;
use function microtime;

class EventListener implements Listener {

	/** @var float[] */
	private array $clickTime = [];

	/** @noinspection PhpUnused */
	public function onAirClick(PlayerItemUseEvent $event): void {
		$session = SessionManager::getInstance()->getSession($player = $event->getPlayer());
		if(!$session->isDrawing()) {
			return;
		}

		$targetBlock = $player->getTargetBlock(BuilderTools::getConfiguration()->getIntProperty("max-ray-trace-distance"));
		if($targetBlock === null) {
			return;
		}

		$position = $targetBlock->getPosition();

		Printer::getInstance()->draw($player, $position, $player->getInventory()->getItemInHand()->getBlock(), $session->getDrawBrush(), $session->getDrawMode(), $session->getDrawFall());
		$event->cancel();
	}

	/** @noinspection PhpUnused */
	public function onBlockBreak(BlockBreakEvent $event): void {
		if(
			($item = $event->getItem()) instanceof WoodenAxe &&
			$item->isWandAxe()
		) {
			$selection = SessionManager::getInstance()->getSession($event->getPlayer())->getSelectionHolder();
			try {
				$selection->handleWandAxeBlockBreak($event->getBlock()->getPosition());
			} catch(RuntimeException $exception) {
				$event->getPlayer()->sendMessage(BuilderTools::getPrefix() . "§c{$exception->getMessage()}");
				return;
			}
			$event->cancel();

			try {
				$size = " ({$selection->size()})";
			} catch(RuntimeException) {
				$size = "";
			}

			$position = $event->getBlock()->getPosition()->floor();
			$event->getPlayer()->sendMessage(BuilderTools::getPrefix() . "§aSelected first position at {$position->getX()}, {$position->getY()}, {$position->getZ()}$size");
		}
	}

	/** @noinspection PhpUnused */
	public function onBlockTouch(PlayerInteractEvent $event): void {
		if($event->getItem() instanceof WoodenAxe && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			$player = $event->getPlayer();
			$item = $event->getItem();
			if(array_key_exists($player->getName(), $this->clickTime) && microtime(true) - $this->clickTime[$player->getName()] < 0.5) {
				$event->cancel();
				return;
			}
			$this->clickTime[$player->getName()] = microtime(true);

			if($item instanceof WoodenAxe && $item->isWandAxe()) {
				$selection = SessionManager::getInstance()->getSession($event->getPlayer())->getSelectionHolder();
				try {
					$selection->handleWandAxeBlockClick($event->getBlock()->getPosition());
				} catch(RuntimeException $exception) {
					$event->getPlayer()->sendMessage(BuilderTools::getPrefix() . "§c{$exception->getMessage()}");
					return;
				}
				$event->cancel();

				try {
					$size = " ({$selection->size()})";
				} catch(RuntimeException) {
					$size = "";
				}

				$position = $event->getBlock()->getPosition()->floor();
				$event->getPlayer()->sendMessage(BuilderTools::getPrefix() . "§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}$size");
				return;
			}

			if($event->getItem()->equals(VanillaItems::STICK(), false, false)) {
				$event->cancel();

				$block = $event->getBlock();
				$world = $event->getBlock()->getPosition()->getWorld();

				$player->sendTip(
					"§aID: §7" . $block->getStateId() . "\n" .
					"§aName: §7" . $block->getName() . "\n" .
					"§aPosition: §7" . $block->getPosition()->getFloorX() . ";" . $block->getPosition()->getFloorY() . ";" . $block->getPosition()->getFloorZ() . " (" . ($block->getPosition()->getFloorX() >> 4) . ";" . ($block->getPosition()->getFloorZ() >> 4) . ")\n" .
					"§aWorld: §7" . $world->getDisplayName() . "\n" .
					"§aBiome: §7" . $world->getBiomeId($block->getPosition()->getFloorX(), $block->getPosition()->getFloorY(), $block->getPosition()->getFloorZ()) . " (" . $world->getBiome($block->getPosition()->getFloorX(), $block->getPosition()->getFloorY(), $block->getPosition()->getFloorZ())->getName() . ")"
				);
			}
		}
	}

	/** @noinspection PhpUnused */
	public function onQuit(PlayerQuitEvent $event): void {
		SessionManager::getInstance()->closeSession($event->getPlayer());
	}

	public function getPlugin(): BuilderTools {
		return BuilderTools::getInstance();
	}
}