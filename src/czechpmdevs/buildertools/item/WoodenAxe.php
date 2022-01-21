<?php

/**
 * Copyright (C) 2018-2022  CzechPMDevs
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

namespace czechpmdevs\buildertools\item;

use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

class WoodenAxe extends Axe {
	protected bool $isWandAxe = false;

	public function setIsWandAxe(bool $isWandAxe): void {
		$this->isWandAxe = $isWandAxe;
	}

	public function isWandAxe(): bool {
		return $this->isWandAxe;
	}

	protected function serializeCompoundTag(CompoundTag $tag): void {
		parent::serializeCompoundTag($tag);
		if($this->isWandAxe) {
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
		}
	}

	public function deserializeCompoundTag(CompoundTag $tag): void {
		parent::deserializeCompoundTag($tag);
		$enchTag = $tag->getListTag(Item::TAG_ENCH);
		if($enchTag !== null && count($enchTag->getValue()) === 0) {
			$tag->removeTag(Item::TAG_ENCH);
			$this->isWandAxe = true;
		}
	}
}