<?php

declare(strict_types=1);

namespace buildertools;

use buildertools\commands\ClearInventoryCommand;
use buildertools\commands\CopyCommand;
use buildertools\commands\DrawCommand;
use buildertools\commands\FillCommand;
use buildertools\commands\FirstPositionCommand;
use buildertools\commands\HelpCommand;
use buildertools\commands\IdCommand;
use buildertools\commands\NaturalizeCommand;
use buildertools\commands\PasteCommand;
use buildertools\commands\ReplaceCommand;
use buildertools\commands\SecondPositionCommand;
use buildertools\commands\SphereCommand;
use buildertools\commands\WandCommand;
use buildertools\editors\Copier;
use buildertools\editors\Editor;
use buildertools\editors\Filler;
use buildertools\editors\Naturalizer;
use buildertools\editors\Printer;
use buildertools\editors\Replacement;
use buildertools\event\listener\EventListener;
use buildertools\task\FillTask;
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
        $this->registerTasks();
    }

    public function sendLoadingInfo() {
        $text = strval(
            "\n".
            "§c--------------------------------\n".
            "§6§lCzechPMDevs §r§e>>> §bBuilderTools\n".
            "§o§9Plugin like WorldEdit for PocketMine servers\n".
            "§aAuthors: §7GamakCZ\n".
            "§aVersion: §7".$this->getDescription()->getVersion()."\n".
            "§aStatus: §7Loading...\n".
            "§c--------------------------------"
        );
        if($this->isEnabled()) {
            $this->getLogger()->info($text);
            $this->getLogger()->info("§a--> Loaded!");
        }
        else {
            $this->getLogger()->critical("§4Submit issue to github.com/CzechPMDevs/BuilderTools/issues  to fix this error!");
        }
    }

    public function registerTasks() {
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new FillTask, 1);
    }

    public function registerEditors() {
        self::$editors["Filler"] = new Filler;
        self::$editors["Printer"] = new Printer;
        self::$editors["Replacement"] = new Replacement;
        self::$editors["Naturalizer"] = new Naturalizer;
        self::$editors["Copier"] = new Copier;
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
        $map->register("BuilderTools", new DrawCommand);
        $map->register("BuilderTools", new SphereCommand);
        $map->register("BuilderTools", new ReplaceCommand);
        $map->register("BuilderTools", new IdCommand);
        $map->register("BuilderTools", new ClearInventoryCommand);
        $map->register("BuilderTools", new NaturalizeCommand);
        $map->register("BuilderTools", new CopyCommand);
        $map->register("BuilderTools", new PasteCommand);
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