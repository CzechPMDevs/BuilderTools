<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\blockstorage;

use pocketmine\level\Level;

/**
 * Class UpdateLevelData
 * @package czechpmdevs\buildertools\blockstorage
 */
class UpdateLevelData extends BlockArray {

    /** @var Level|null $level */
    protected ?Level $level = null;

    /**
     * @return Level|null
     */
    public function getLevel(): ?Level {
        return $this->level;
    }

    /**
     * @param Level|null $level
     *
     * @return $this
     */
    public function setLevel(?Level $level): self {
        $this->level = $level;

        return $this;
    }

    /**
     * @param BlockArray $blockArray
     * @param Level|null $level
     *
     * @return UpdateLevelData
     */
    public static function fromBlockArray(BlockArray $blockArray, ?Level $level = null): UpdateLevelData {
        $data = new UpdateLevelData($blockArray->detectingDuplicates());
        $data->buffer = $blockArray->buffer;
        $data->offset = $blockArray->offset;

        return $data->setLevel($level);
    }
}