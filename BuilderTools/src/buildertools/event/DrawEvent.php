<?php

declare(strict_types=1);

namespace buildertools\event;

use pocketmine\event\Cancellable;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class DrawEvent extends BuilderToolsEvent implements Cancellable {

    /** @var Player $player */
    protected $player;

    /** @var Level $level */
    protected $level;

    /**
     * @var Vector3 $center
     */
    protected $center;

    /**
     * DrawEvent constructor.
     * @param Player $player
     * @param Level $level
     * @param Vector3 $center
     * @param array $settings
     */
    public function __construct(Player $player, Level $level, Vector3 $center, array $settings) {
        $this->player = $player;
        $this->level = $level;
        $this->center = $center;
        parent::__construct($settings);
    }

    /**
     * @return Player $player
     */
    public function getPlayer(): Player {
        return $this->player;
    }

    /**
     * @return Level $level
     */
    public function getLevel(): Level {
        return $this->level;
    }

    /**
     * @return Vector3 $center
     */
    public function getCenter(): Vector3 {
        return $this->center;
    }
}
