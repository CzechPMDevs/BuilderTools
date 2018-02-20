<?php

declare(strict_types=1);

namespace buildertools\utils;

use buildertools\BuilderTools;

/**
 * Class ConfigManager
 * @package buildertools\utils
 */
class ConfigManager {

    /** @var BuilderTools $plugin */
    public $plugin;

    /**
     * ConfigManager constructor.
     * @param BuilderTools $plugin
     */
    public function __construct(BuilderTools $plugin) {
        $this->plugin = $plugin;
    }

    public function loadConfig() {
        if(!is_dir($this->plugin->getDataFolder())) {
            @mkdir($this->plugin->getDataFolder());
        }
    }
}
