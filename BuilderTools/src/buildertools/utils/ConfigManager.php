<?php

declare(strict_types=1);

namespace buildertools\utils;

use buildertools\BuilderTools;
use buildertools\editors\Editor;
use pocketmine\utils\Config;

/**
 * Class ConfigManager
 * @package buildertools\utils
 */
class ConfigManager {

    /** @var BuilderTools $plugin */
    private $plugin;

    /** @var array $config */
    protected static $config = [];

    /**
     * ConfigManager constructor.
     * @param BuilderTools $plugin
     */
    public function __construct(BuilderTools $plugin) {
        $this->plugin = $plugin;
        $this->loadConfig();
    }

    protected function loadConfig() {
        // create data folder
        if(!is_dir($this->plugin->getDataFolder())) {
            @mkdir($this->plugin->getDataFolder());
        }
        // save default config
        if(!is_file($this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . "config.yml")) {
            $this->plugin->saveResource(DIRECTORY_SEPARATOR . "config.yml", \false);
        }
        // loads config
        $config = new Config($this->plugin->getDataFolder() . DIRECTORY_SEPARATOR . "config.yml", Config::YAML);
        self::$config = $config->getAll();

        // debug
        Log::debug("Config loaded!", $this);
        if(self::$config["debug"]) var_dump(self::$config);
    }

    /**
     * @param Editor $editor
     * @return array $settings
     */
    public static function getSettings(Editor $editor): array {
        return isset(self::$config[strtolower($editor->getName())]) ? self::$config[strtolower($editor->getName())] : [];
    }
}
