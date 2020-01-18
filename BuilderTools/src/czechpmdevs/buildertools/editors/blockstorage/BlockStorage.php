<?php

/**
 * Copyright (C) 2018-2020  CzechPMDevs
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

namespace czechpmdevs\buildertools\editors\blockstorage;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

/**
 * Interface BlockStorage
 * @package czechpmdevs\buildertools\editors\blockstorage
 */
interface BlockStorage {

    /**
     * @param Vector3 $position
     * @param Block $block
     */
    public function addBlock(Vector3 $position, Block $block): void ;

    /**
     * @param Block[] $blocks
     */
    public function setAll(array $blocks): void ;

    /**
     * @return Block[] $blocks
     */
    public function getAll(): array;

    /**
     * @param Level|null $level
     */
    public function setLevel(?Level $level): void;

    /**
     * @return Level|null
     */
    public function getLevel(): ?Level;
}