<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class SelectionData
 * @package czechpmdevs\buildertools\blockstorage
 */
class SelectionData extends BlockArray {

    /** @var Player $player */
    protected Player $player;
    /** @var Vector3 $playerPosition */
    protected Vector3 $playerPosition;

    /**
     * @return Player
     */
    public function getPlayer(): Player {
        return $this->player;
    }

    /**
     * @param Player $player
     * @return $this
     */
    public function setPlayer(Player $player): self {
        $this->player = $player;

        return $this;
    }

    /**
     * @return Vector3
     */
    public function getPlayerPosition(): Vector3 {
        return $this->playerPosition;
    }

    /**
     * @param Vector3 $playerPosition
     * @return $this
     */
    public function setPlayerPosition(Vector3 $playerPosition): self {
        $this->playerPosition = $playerPosition;

        return $this;
    }
}