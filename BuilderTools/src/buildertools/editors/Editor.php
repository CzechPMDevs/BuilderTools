<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;

/**
 * Class Editor
 * @package buildertools\editors
 */
abstract class Editor {

    /**
     * @return string
     */
    abstract function getName():string;

    /**
     * @return BuilderTools
     */
    public function getPlugin():BuilderTools {
        return BuilderTools::getInstance();
    }
}