<?php

/**
 * Copyright (C) 2018-2021  CzechPMDevs
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace czechpmdevs\buildertools\utils;

use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use OutOfBoundsException;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use function array_rand;
use function count;
use function explode;
use function in_array;
use function is_numeric;
use function min;
use function str_replace;
use function strpos;
use function substr;

final class StringToBlockDecoder implements BlockIdentifierList {

	private string $string;

	private ?string $itemInHand = null;

	/** @var int[] */
	private array $blockIdMap = [];
	/** @var int[] */
	private array $blockMap = [];

	public function __construct(string $string, ?Item $handItem = null, bool $mixBlockIds = true) {
		$this->string = $string;

		if($handItem !== null) {
			$this->itemInHand = "{$handItem->getId()}:{$handItem->getMeta()}";
		}

		$this->decode($mixBlockIds);
	}

	/**
	 * @return bool Returns if the string contains
	 * any valid blocks
	 */
	public function isValid(bool $requireBlockMap = true): bool {
		return count($this->blockMap) != 0 || (!$requireBlockMap && count($this->blockIdMap) != 0);
	}

	/**
	 * Reads next block from the string,
	 * @throws OutOfBoundsException if string is not valid.
	 */
	public function nextBlock(?int &$id, ?int &$meta): void {
		$hash = $this->blockMap[array_rand($this->blockMap)];

		$id = $hash >> 4;
		$meta = $hash & 0xf;
	}

	/**
	 * @return bool Returns if the block is in the array
	 */
	public function containsBlock(int $blockHash): bool {
		return in_array($blockHash, $this->blockMap, true);
	}

	/**
	 * @return bool Returns if block id is in the array
	 */
	public function containsBlockId(int $id): bool {
		return in_array($id, $this->blockIdMap, true);
	}

	/**
	 * @param bool $mixBlockIds If enabled, block ids will be saved
	 * to both block and blockId maps
	 */
	public function decode(bool $mixBlockIds = true): void {
		if($this->itemInHand !== null) {
			$this->string = str_replace("hand", $this->itemInHand, $this->string);
		}

		$split = explode(",", str_replace(";", ",", $this->string));
		foreach($split as $entry) {
			$count = 1;
			$block = $entry;
			if(strpos($entry, "%") !== false) {
				$p = substr($entry, 0, $pos = strpos($entry, "%"));
				if(!is_numeric($p)) {
					continue;
				}

				$count = min(100, (int)$p);
				$block = substr($entry, $pos + 1);
			}

			try {
				$item = LegacyStringToItemParser::getInstance()->parse($block);
			} catch(LegacyStringToItemParserException $ignore) {
				continue; // Item not found
			}

			$class = $item->getBlock();
			if($class->getId() == 0 && $item->getId() != 0) {
				continue;
			}

			if(!$mixBlockIds) {
				if(strpos($entry, ":") !== false) { // Meta is specified
					for($i = 0; $i < $count; ++$i) {
						$this->blockMap[] = $class->getId() << 4 | $class->getMeta();
					}
				} else {
					for($i = 0; $i < $count; ++$i) {
						$this->blockIdMap[] = $class->getId();
					}
				}
				continue;
			}

			for($i = 0; $i < $count; ++$i) {
				$this->blockIdMap[] = $class->getId();
				$this->blockMap[] = $class->getId() << 4 | $class->getMeta();
			}
		}
	}
}