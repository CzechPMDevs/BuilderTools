<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\schematics\format;

use czechpmdevs\buildertools\async\schematics\MCEditSaveTask;
use czechpmdevs\buildertools\math\Math;
use czechpmdevs\buildertools\schematics\SchematicData;
use czechpmdevs\buildertools\Selectors;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use function max;
use function min;

class MCEditSchematics extends SchematicData {

    public function __construct() {
        parent::__construct();
    }

    public function save(string $targetFile): void {
        Server::getInstance()->getAsyncPool()->submitTask(new MCEditSaveTask($this));
    }

    public static function create(Player $player, string $file): SchematicData {
        $schematic = new MCEditSchematics();

        /** @var Vector3 $pos1 */
        $pos1 = Selectors::getPosition($player, 1);
        /** @var Vector3 $pos2 */
        $pos2 = Selectors::getPosition($player, 2);

        $schematic->setAxisVector(Math::calculateAxisVec($pos1, $pos2));
        $schematic->setFile($file);

        $minY = (int)min($pos1->getY(), $pos2->getY());
        $maxY = (int)max($pos1->getY(), $pos2->getY());
        $minZ = (int)min($pos1->getZ(), $pos2->getZ());
        $maxZ = (int)max($pos1->getZ(), $pos2->getZ());
        $minX = (int)min($pos1->getX(), $pos2->getX());
        $maxX = (int)max($pos1->getX(), $pos2->getX());

        for($y = $minY; $y <= $maxY; ++$y) {
            for ($z = $minZ; $z <= $maxZ; ++$z) {
                for ($x = $minX; $x <= $maxX; ++$x) {
                    $schematic->addBlock(new Vector3($x, $y, $z), $player->getLevelNonNull()->getBlockIdAt($x, $y, $z), $player->getLevelNonNull()->getBlockDataAt($x, $y, $z));
                }
            }
        }

        return $schematic;
    }
}