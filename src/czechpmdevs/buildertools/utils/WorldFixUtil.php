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
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use function array_key_exists;
use function basename;
use function in_array;
use function is_dir;

class WorldFixUtil {

    /** @var string[] */
    private static array $worldFixQueue = [];

    /** @noinspection PhpUnusedParameterInspection */
    public static function fixWorld(CommandSender $sender, string $worldName): void {
        if(WorldFixUtil::isInWorldFixQueue($worldName)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cServer is already fixing this world!");
            return;
        }
        if(in_array($sender->getName(), WorldFixUtil::$worldFixQueue)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cYou cannot fix more than one world at the same time!");
            return;
        }

        if(Server::getInstance()->getDefaultLevel() !== null && Server::getInstance()->getDefaultLevel()->getFolderName() == $worldName) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cYou cannot fix default world!");
            return;
        }

        $path = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $worldName;
        if(!is_dir($path)) {
            $sender->sendMessage(BuilderTools::getPrefix() . "§cLevel not found.");
            return;
        }

        WorldFixUtil::$worldFixQueue[$worldName] = $sender->getName();

        if(Server::getInstance()->isLevelLoaded($worldName)) {
            /** @phpstan-ignore-next-line */
            Server::getInstance()->unloadLevel(Server::getInstance()->getLevelByName($worldName), true);
        }

        $asyncTask = new WorldFixTask($path);

        BuilderTools::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($asyncTask): void { // Delay until the world will be fully saved
            Server::getInstance()->getAsyncPool()->submitTask($asyncTask);
        }), 60);

        /** @var ClosureTask $task */
        $task = null;

        BuilderTools::getInstance()->getScheduler()->scheduleDelayedRepeatingTask($task = new ClosureTask(function (int $currentTick) use ($worldName, $asyncTask, $sender, &$task): void {
            if($sender instanceof Player) {
                if($sender->isOnline()) {
                    $sender->sendTip("§aWorld $worldName is fixed from $asyncTask->percent%.");
                } else {
                    $asyncTask->forceStop = true;
                    goto finish;
                }
            }

            if($asyncTask->error != "") {
                $sender->sendMessage(BuilderTools::getPrefix() . "§c" . $asyncTask->error);
                goto finish;
            }

            if($asyncTask->done) {
                $sender->sendMessage(BuilderTools::getPrefix() . "§aWorld fix task completed in $asyncTask->time seconds, ($asyncTask->chunkCount chunks updated)!");

                finish:
                BuilderTools::getInstance()->getScheduler()->cancelTask($task->getTaskId());
                WorldFixUtil::finishWorldFixTask($asyncTask);
            }
        }), 60, 2);

        $sender->sendMessage(BuilderTools::getPrefix() . "§aFixing the world...");
    }

    public static function isInWorldFixQueue(string $levelName): bool {
        return array_key_exists($levelName, WorldFixUtil::$worldFixQueue);
    }

    /**
     * @internal
     *
     * @param WorldFixTask<mixed> $task
     */
    public static function finishWorldFixTask(WorldFixTask $task): void {
        unset(WorldFixUtil::$worldFixQueue[basename($task->worldPath)]);
    }
}