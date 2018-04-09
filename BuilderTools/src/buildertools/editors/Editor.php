<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;

/**
 * Class Editor
 * @package buildertools\editors
 */
abstract class Editor {

    const CANCELLER = "Canceller";
    const COPIER = "Copier";
    const DECORATOR = "Decorator";
    const FILLER = "Filler";
    const FIXER = "Fixer";
    const NATURALIZER = "Naturalizer";
    const PRINTER = "Printer";
    const REPLACEMENT = "Replacement";

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