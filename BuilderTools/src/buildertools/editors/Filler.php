<?php

declare(strict_types=1);

namespace buildertools\editors;

use buildertools\BuilderTools;
use buildertools\task\async\FillAsyncTask;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

/**
 * Class Filler
 * @package buildertools\editors
 */
class Filler extends Editor {

    /**
     * @param int $x1
     * @param int $y1
     * @param int $z1
     * @param int $x2
     * @param int $y2
     * @param int $z2
     * @param Player $player
     * @param Level $level
     * @param string $blocks
     * @param bool $async
     * @return void
     */
    public function fill(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2, Player $player, Level $level, string $blocks, bool $async) {

        if($async) {
            $data = [
                "player" => $player->getName(),
                "pos1" => array($x1, $y1, $z1, $level->getFolderName()),
                "pos2" => array($x2, $y2, $z2, $level->getFolderName()),
                "blocks" => $blocks
            ];
            $task = new FillAsyncTask($data);

            
            Server::getInstance()->getAsyncPool()->submitTask($task);
            return;
        }

        $time = microtime(true);

        /** @var array $undo */
        $undo = [];

        $count = 0;
        for($x = min($x1, $x2); $x <= max($x1, $x2); $x++) {
            for ($y = min($y1, $y2); $y <= max($y1, $y2); $y++) {
                for ($z = min($z1, $z2); $z <= max($z1, $z2); $z++) {
                    $count++;
                    $args = explode(",", strval($blocks));
                    $undo[] = $level->getBlock(new Vector3($x, $y, $z));
                    $level->setBlock(new Vector3($x, $y, $z), Item::fromString($args[array_rand($args, 1)])->getBlock(), true, true);
                }
            }
        }

        /** @var Canceller $canceller */
        $canceller = BuilderTools::getEditor("Canceller");
        $canceller->addStep($player, $undo);

        $time = round(microtime(true)-$time, 4);

        $player->sendMessage(BuilderTools::getPrefix()."§aSelected area successfully filled in ($time) sec! ($count blocks changed)!");
        return;
    }

    public function getName(): string {
        return "Filler";
    }
}