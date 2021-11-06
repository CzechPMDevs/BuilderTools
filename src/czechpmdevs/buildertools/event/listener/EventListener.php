<?php

/**
 * Copyright (C) 2018-2021  CzechPMDevs
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

use czechpmdevs\buildertools\blockstorage\OfflineSession;
use czechpmdevs\buildertools\BuilderTools;
use czechpmdevs\buildertools\editors\Printer;
use czechpmdevs\buildertools\Selectors;
use czechpmdevs\buildertools\utils\WorldFixUtil;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Server;
use function is_int;
use function microtime;

class EventListener implements Listener {

	/** @var float[] */
	private array $wandClicks = [];
	/** @var float[] */
	private array $blockInfoClicks = [];

	/** @noinspection PhpUnused */
	public function onAirClick(PlayerItemUseEvent $event): void {
		if(!Selectors::isDrawingPlayer($player = $event->getPlayer())) {
			return;
		}

		$targetBlock = $player->getTargetBlock(64);
		if($targetBlock === null) {
			return;
		}

		$position = $targetBlock->getPosition();

		Printer::getInstance()->draw($player, $position, $player->getInventory()->getItemInHand()->getBlock(), Selectors::getDrawingPlayerBrush($player), Selectors::getDrawingPlayerMode($player), Selectors::getDrawingPlayerFall($player));
		$event->cancel();
	}

	/** @noinspection PhpUnused */
	public function onBlockBreak(BlockBreakEvent $event): void {
		if(Selectors::isWandSelector($player = $event->getPlayer()) || ($event->getItem()->getId() == ItemIds::WOODEN_AXE && $event->getItem()->getNamedTag()->getTag("buildertools") instanceof ByteTag)) {
			$size = Selectors::addSelector($player, 1, $position = $event->getBlock()->getPosition());
			$player->sendMessage(BuilderTools::getPrefix() . "§aSelected first position at {$position->getX()}, {$position->getY()}, {$position->getZ()}" . (is_int($size) ? " ($size)" : ""));
			$event->cancel();
		}
	}

	/** @noinspection PhpUnused */
	public function onBlockTouch(PlayerInteractEvent $event): void {
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
		if(Selectors::isWandSelector($player = $event->getPlayer()) || ($event->getItem()->getId() == ItemIds::WOODEN_AXE && $event->getItem()->getNamedTag()->getTag("buildertools") instanceof ByteTag)) {
			// antispam ._.
			if(isset($this->wandClicks[$player->getName()]) && microtime(true) - $this->wandClicks[$player->getName()] < 0.5) {
				return;
			}

			$this->wandClicks[$player->getName()] = microtime(true);
			$size = Selectors::addSelector($player, 2, $position = $event->getBlock()->getPosition());
			$player->sendMessage(BuilderTools::getPrefix() . "§aSelected second position at {$position->getX()}, {$position->getY()}, {$position->getZ()}" . (is_int($size) ? " ($size)" : ""));
			$event->cancel();
		}

		if(Selectors::isBlockInfoPlayer($player = $event->getPlayer()) || ($event->getItem()->getId() == ItemIds::STICK && $event->getItem()->getNamedTag()->getTag("buildertools") instanceof ByteTag)) {
			// antispam ._.
			if(isset($this->blockInfoClicks[$player->getName()]) && microtime(true) - $this->blockInfoClicks[$player->getName()] < 0.5) {
				return;
			}

			$block = $event->getBlock();
			$this->blockInfoClicks[$player->getName()] = microtime(true);

			$world = $block->getPosition()->getWorld();

			$player->sendTip("§aID: §7" . $block->getId() . ":" . $block->getMeta() . "\n" .
				"§aName: §7" . $block->getName() . "\n" .
				"§aPosition: §7" . $block->getPosition()->getFloorX() . ";" . $block->getPosition()->getFloorY() . ";" . $block->getPosition()->getFloorZ() . " (" . ($block->getPosition()->getFloorX() >> 4) . ";" . ($block->getPosition()->getFloorZ() >> 4) . ")\n" .
				"§World: §7" . $world->getDisplayName() . "\n" .
				"§aBiome: §7" . $block->getPosition()->getWorld()->getBiomeId($block->getPosition()->getFloorX(), $block->getPosition()->getFloorZ()) . " (" . $block->getPosition()->getWorld()->getBiome($block->getPosition()->getFloorX(), $block->getPosition()->getFloorZ())->getName() . ")");
		}
	}

	/** @noinspection PhpUnused */
	public function onLevelLoad(WorldLoadEvent $event): void {
		if(WorldFixUtil::isInWorldFixQueue($event->getWorld()->getFolderName())) {
			Server::getInstance()->getWorldManager()->unloadWorld($event->getWorld(), true);
		}
	}

	/** @noinspection PhpUnused */
	public function onJoin(PlayerJoinEvent $event): void {
		OfflineSession::loadPlayerSession($event->getPlayer());
	}

	/** @noinspection PhpUnused */
	public function onQuit(PlayerQuitEvent $event): void {
		OfflineSession::savePlayerSession($event->getPlayer());
		Selectors::unloadPlayer($event->getPlayer());
	}

	public function getPlugin(): BuilderTools {
		return BuilderTools::getInstance();
	}
}