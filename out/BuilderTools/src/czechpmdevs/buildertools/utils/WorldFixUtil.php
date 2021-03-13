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

namespace czechpmdevs\buildertools\utils;

use czechpmdevs\buildertools\async\WorldFixTask;
use czechpmdevs\buildertools\BuilderTools;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class WorldFixUtil {

    /** @var string[] */
    private static $worldFixQueue = [];

    public static function fixWorld(CommandSender $sender, string $worldName): void {
        if(self::isInWorldFixQueue($worldName)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cServer is already fixing this world!");
            return;
        }
        if(in_array($sender->getName(), self::$worldFixQueue)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cYou cannot fix more than one world at the same time!");
            return;
        }
        if(Server::getInstance()->getDefaultLevel()->getFolderName() == $worldName) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cYou cannot fix default world!");
            return;
        }

        $path = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $worldName;
        if(!is_dir($path)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cLevel not found.");
            return;
        }

        self::$worldFixQueue[$worldName] = $sender->getName();

        if(Server::getInstance()->isLevelLoaded($worldName)) {
            Server::getInstance()->unloadLevel(Server::getInstance()->getLevelByName($worldName), true);
        }

        $asyncTask = new WorldFixTask($path);
        BuilderTools::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($asyncTask): void { // Delay until the world will be fully saved
            Server::getInstance()->getAsyncPool()->submitTask($asyncTask);
        }), 60);

        BuilderTools::getInstance()->getScheduler()->scheduleRepeatingTask(new class($asyncTask, $sender) extends Task {
            /** @var WorldFixTask */
            private $task;
            /** @var CommandSender */
            private $sender;

            /** @var float $lastPercent */
            private $lastPercent = 0;

            public function __construct(WorldFixTask $task, CommandSender $sender) {
                $this->task = $task;
                $this->sender = $sender;
            }

            public function onRun(int $currentTick) {
                if($this->sender instanceof Player && !$this->sender->isOnline()) {
                    $this->task->forceStop = true;
                    goto finish;
                }

                if($this->task->error != "") {
                    $this->sender->sendMessage(BuilderTools::getPrefix() . "§c" . $this->task->error);
                    goto finish;
                }

                if($this->task->percentage != $this->lastPercent) {
                    if($this->sender instanceof Player && $this->task->percentage > 0) {
                        $this->sender->sendTip(BuilderTools::getPrefix() . "§aWorld is fixed from " . $this->task->percentage . "%%%");
                    }
                    $this->lastPercent = $this->task->percentage;
                    return;
                }

                if($this->task->percentage == -1) {
                    $this->sender->sendMessage(BuilderTools::getPrefix() . "§aWorld fix task completed in {$this->task->time} ({$this->task->chunkCount} chunks updated)!");

                    finish:
                    BuilderTools::getInstance()->getScheduler()->cancelTask($this->getTaskId());
                    WorldFixUtil::finishWorldFixTask($this->task);
                    return;
                }
            }
        }, 2);

        $sender->sendMessage(BuilderTools::getPrefix() . "§aFixing the world...");
    }

    public static function isInWorldFixQueue(string $levelName): bool {
        return isset(self::$worldFixQueue[$levelName]);
    }

    /**
     * @internal
     */
    public static function finishWorldFixTask(WorldFixTask $task) {
        unset(self::$worldFixQueue[basename($task->worldPath)]);
    }
}