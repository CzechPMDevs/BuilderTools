<?php

declare(strict_types=1);

namespace buildertools\event;

use buildertools\BuilderTools;
use pocketmine\event\plugin\PluginEvent;

/**
 * Class BuilderToolsEvent
 * @package buildertools\event
 */
abstract class BuilderToolsEvent extends PluginEvent {

    /** @var null $handlerList */
    public static $handlerList = \null;

    /** @var array $settings */
    protected $settings;

    /**
     * BuilderToolsEvent constructor.
     */
    public function __construct(array $settings) {
        $this->settings = $settings;
        parent::__construct(BuilderTools::getInstance());
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings) {
        $this->settings = $settings;
    }

    /**
     * @return array $settings
     */
    public function getSettings(): array {
        return $this->settings;
    }
}
