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

/**
 * Class made for debug(s)
 */

declare(strict_types=1);

namespace buildertools\utils;

use buildertools\BuilderTools;
use pocketmine\utils\TextFormat;

/**
 * Class Log
 * @package buildertools\utils
 */
abstract class Log extends ConfigManager {

    /**
     * @param string $message
     * @param null $class
     */
    public static function error(string $message, $class = \null) {
        $text = "";
        if($class !== \null) $text .= TextFormat::GOLD . "[".get_class($class)."] " . TextFormat::RED;
        $text .= $message;
        BuilderTools::getInstance()->getLogger()->error($text);
    }

    /**
     * @param string $message
     * @param null $class
     */
    public static function info(string  $message, $class = \null) {
        $text = "";
        if($class !== \null) $text .= TextFormat::GOLD . "[".get_class($class)."] " . TextFormat::RESET;
        $text .= $message;
        BuilderTools::getInstance()->getLogger()->info($text);
    }

    /**
     * @param string $message
     * @param null $class
     */
    public static function debug(string $message, $class = \null) {
        if(self::$config["debug"] || self::$config["debug"] == "true") {
            $text = "";
            if($class !== \null) $text .= TextFormat::GOLD . "[".get_class($class)."] " . TextFormat::AQUA;
            $text .= $message;
            BuilderTools::getInstance()->getLogger()->debug($text);
        }
    }
}
