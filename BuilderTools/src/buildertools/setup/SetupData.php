<?php

declare(strict_types=1);

namespace buildertools\setup;

/**
 * Class SetupData
 * @package buildertools\setup
 */
class SetupData {

    /**
     * @var array
     */
    private static $setup = [
        "FillingStyle" => 0,
        "MinBrush" => 1,
        "MaxBrush" => 6,
    ];

    /**
     * @param string $option
     * @return int
     */
    public static function getOption(string $option):int {
        return isset(self::$setup[$option]) ? intval(self::$setup[$option]) : 0;
    }

    /**
     * @param string $option
     * @param int $setup
     */
    public static function setOption(string $option, int $setup) {
        self::$setup[$option] = $setup;
    }
}