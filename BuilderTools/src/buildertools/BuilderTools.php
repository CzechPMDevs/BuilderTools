<?php

declare(strict_types=1);

namespace buildertools;

use buildertools\commands\FillCommand;
use buildertools\commands\FirstPositionCommand;
use buildertools\commands\HelpCommand;
use buildertools\commands\SecondPositionCommand;
use buildertools\commands\WandCommand;
use buildertools\editors\Editor;
use buildertools\editors\Filler;
use buildertools\events\listener\EventListener;
use pocketmine\plugin\PluginBase;

/**
 * Class BuilderTools
 * @package buildertools
 */
class BuilderTools extends PluginBase {

    /** @var  BuilderTools $instance */
    private static $instance;

    /** @var  string $prefix */
    private static $prefix;

    /** @var  Editor[] $editors */
    private static $editors = [];

    public function onEnable() {
        self::$instance = $this;
        self::$prefix = "§7[BuilderTools] §a";
        $this->registerCommands();
        $this->initListner();
        $this->registerEditors();
        $this->sendLoadingInfo();
    }

    public function sendLoadingInfo() {
        $text = strval(
            "\n".
            "§6§lCzechPMDevs §r§e>>> §bBuilderTools\n".
            "§o§9WorldEdit plugin for PocketMine\n".
            "§7Authors: GamakCZ\n".
            "§7Version: ".$this->getDescription()->getVersion()."\n"
        );
        if($this->isEnabled()) {
            $this->getLogger()->info($text);
        }
        else {
            $this->getLogger()->critical("§4Submit issue to github.com/CzechPMDevs/BuilderTools/issues  to fix this error!");
        }
    }

    public function registerEditors() {
        self::$editors["Filler"] = new Filler;
    }

    public function initListner() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener, $this);
    }

    public function registerCommands() {
        $map = $this->getServer()->getCommandMap();
        $map->register("BuilderTools", new FirstPositionCommand);
        $map->register("BuilderTools", new SecondPositionCommand);
        $map->register("BuilderTools", new WandCommand);
        $map->register("BuilderTools", new FillCommand);
        $map->register("BuilderTools", new HelpCommand);
    }

    /**
     * @param string $name
     * @return Editor
     */
    public static function getEditor(string $name):Editor {
        return self::$editors[$name];
    }

    /**
     * @return string
     */
    public static function getPrefix():string {
        return self::$prefix;
    }

    /**
     * @return BuilderTools $instance
     */
    public static function getInstance():BuilderTools {
        return self::$instance;
    }
}