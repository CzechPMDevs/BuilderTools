<?php

declare(strict_types=1);

namespace buildertools\editors\object;

/**
 * Class EditorResult
 * @package buildertools\editors\object
 */
class EditorResult {

    /** @var int $countBlocks */
    public $countBlocks;

    /** @var float|int $time */
    public $time;

    /**
     * EditorResult constructor.
     * @param int $countBlocks
     * @param float|int $time
     */
    public function __construct(int $countBlocks, ?float $time) {
        $this->countBlocks = $countBlocks;
        $this->time = $time;
    }
}