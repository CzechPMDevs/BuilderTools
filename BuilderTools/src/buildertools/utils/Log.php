<?php

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
