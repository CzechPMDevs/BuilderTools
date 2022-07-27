<?php

declare(strict_types=1);

namespace czechpmdevs\buildertools\utils;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\StringToTParser;

/**
 * Handles parsing blocks from strings.
 *
 * @phpstan-extends StringToTParser<Block>
 */
final class StringToBlockParser extends StringToTParser {
	use SingletonTrait;

	/** @phpstan-ignore-next-line */
	private static function make(): self {
		$result = new self;

		$result->register("air", fn() => VanillaBlocks::AIR());

		foreach(StringToItemParser::getInstance()->getKnownAliases() as $alias) {
			if(!is_string($alias)) {
				continue;
			}

			$item = StringToItemParser::getInstance()->parse($alias);
			if($item === null) {
				continue;
			}

			$block = $item->getBlock();
			if($block->isSameType(VanillaBlocks::AIR())) {
				continue;
			}

			$result->register($alias, fn() => $item->getBlock());
		}

		foreach(BlockFactory::getInstance()->getAllKnownStates() as $state) {
			try {
				$result->register("{$state->getStateId()}", fn() => $state);
			} catch(InvalidArgumentException) {
			}
		}

		return $result;
	}

	public function parse(string $input): ?Block {
		return parent::parse($input);
	}
}