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

namespace czechpmdevs\buildertools\async;

use Closure;
use pocketmine\Server;
use function spl_object_hash;

class AsyncQueue {

    /** @var Closure[] */
    private static array $queue = [];

    /**
     * @phpstan-param BuilderToolsAsyncTask<mixed> $task
     * @phpstan-param Closure(BuilderToolsAsyncTask<mixed> $task): void $callback
     */
    public static function submitTask(BuilderToolsAsyncTask $task, ?Closure $callback = null): void {
        Server::getInstance()->getAsyncPool()->submitTask($task);

        if($callback !== null) {
            self::$queue[spl_object_hash($task)] = $callback;
        }
    }

    /**
     * @internal
     *
     * @phpstan-param BuilderToolsAsyncTask<mixed> $task
     */
    public static function callCallback(BuilderToolsAsyncTask $task): void {
        if(!isset(self::$queue[spl_object_hash($task)])) {
            return;
        }

        $callback = self::$queue[spl_object_hash($task)];
        $callback($task);
    }
}