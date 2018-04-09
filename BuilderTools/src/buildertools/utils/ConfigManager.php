<?php

declare(strict_types=1);

namespace buildertools\utils;

use buildertools\BuilderTools;
use buildertools\editors\Canceller;
use buildertools\editors\Editor;
use buildertools\editors\Filler;
use pocketmine\utils\Config;

/**
 * Class ConfigManager
 * @package buildertools\utils
 */
class ConfigManager {

    /** @var BuilderTools $plugin */
    private $plugin;

    /** @var array $config */
    private static $config = [];

    /**
     * ConfigManager constructor.
     * @param BuilderTools $plugin
     */
    public function __construct(BuilderTools $plugin) {
        $this->plugin = $plugin;
        $this->loadConfig();
    }

    protected function loadConfig() {
        if(!is_dir($this->plugin->getDataFolder())) {
            @mkdir($this->plugin->getDataFolder());
        }
        if(!is_file($this->plugin->getDataFolder())) {
            $this->plugin->saveResource("/config.yml", true);
        }
        $config = new Config($this->plugin->getDataFolder()."/config.yml", Config::YAML);
        self::$config = $config->getAll();
    }

    /**
     * @param Editor $editor
     * @return array $settings
     */
    public static function getSettings(Editor $editor): array {
        return isset(self::$config[strtolower(get_class($editor))]) ? self::$config[strtolower(get_class($editor))] : [];
    }
}
