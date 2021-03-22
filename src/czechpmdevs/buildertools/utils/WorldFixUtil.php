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

use czechpmdevs\buildertools\async\convert\WorldFixTask;
use czechpmdevs\buildertools\BuilderTools;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use function basename;

class WorldFixUtil {

    /** @var string[] */
    private static array $worldFixQueue = [];

    /** @noinspection PhpUnusedParameterInspection */
    public static function fixWorld(CommandSender $sender, string $worldName): void {
        if(self::isInWorldFixQueue($worldName)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cServer is already fixing this world!");
            return;
        }
        if(in_array($sender->getName(), self::$worldFixQueue)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cYou cannot fix more than one world at the same time!");
            return;
        }

        if(Server::getInstance()->getWorldManager()->getDefaultWorld() !== null && Server::getInstance()->getWorldManager()->getDefaultWorld() == $worldName) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cYou cannot fix default world!");
            return;
        }

        $path = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $worldName;
        if(!is_dir($path)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cLevel not found.");
            return;
        }

        self::$worldFixQueue[$worldName] = $sender->getName();

        if(Server::getInstance()->getWorldManager()->isWorldLoaded($worldName)) {
            /** @phpstan-ignore-next-line */
            Server::getInstance()->getWorldManager()->unloadWorld(Server::getInstance()->getWorldManager()->getWorldByName($worldName), true);
        }

        $asyncTask = new WorldFixTask($path);

        BuilderTools::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($asyncTask): void { // Delay until the world will be fully saved
            Server::getInstance()->getAsyncPool()->submitTask($asyncTask);
        }), 60);

        /** @var ClosureTask $task */
        $task = null;

        $lastPercent = 0;
        BuilderTools::getInstance()->getScheduler()->scheduleDelayedRepeatingTask($task = new ClosureTask(function (int $currentTick) use (&$lastPercent, $asyncTask, $sender, &$task): void {
            if($sender instanceof Player && !$sender->isOnline()) {
                $asyncTask->forceStop = true;
                goto finish;
            }

            if($asyncTask->error != "") {
                $sender->sendMessage(BuilderTools::getPrefix() . "§c" . $asyncTask->error);
                goto finish;
            }

            if($asyncTask->percentage != $lastPercent) {
                if($sender instanceof Player && $asyncTask->percentage > 0) {
                    $sender->sendTip(BuilderTools::getPrefix() . "§aWorld is fixed from " . $asyncTask->percentage . "%%%");
                }
                $lastPercent = $asyncTask->percentage;
                return;
            }

            if($asyncTask->percentage == -1) {
                $sender->sendMessage(BuilderTools::getPrefix() . "§aWorld fix task completed in $asyncTask->time ($asyncTask->chunkCount chunks updated)!");

                finish:
                $task->getHandler()->cancel(); // TODO - Check if it's cancelled right
                WorldFixUtil::finishWorldFixTask($asyncTask);
            }
        }), 1, 2);

        $sender->sendMessage(BuilderTools::getPrefix() . "§aFixing the world...");
    }

    public static function isInWorldFixQueue(string $levelName): bool {
        return isset(self::$worldFixQueue[$levelName]);
    }

    /**
     * @internal
     *
     * @param WorldFixTask<mixed> $task
     */
    public static function finishWorldFixTask(WorldFixTask $task): void {
        if(isset(self::$worldFixQueue[basename($task->worldPath)])) {
            unset(self::$worldFixQueue[basename($task->worldPath)]);
        }
    }
}