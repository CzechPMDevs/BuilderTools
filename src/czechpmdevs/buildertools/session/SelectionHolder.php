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

namespace czechpmdevs\buildertools\session;

use Closure;
use czechpmdevs\buildertools\blockstorage\identifiers\BlockIdentifierList;
use czechpmdevs\buildertools\editors\object\UpdateResult;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;

abstract class SelectionHolder {
	final public function __construct(
		protected Session $session
	) {}

	/**
	 * @return int Returns size of the selection.
	 *
	 * @throws RuntimeException If there is not enough positions set to calculate size.
	 */
	abstract public function size(): int;

	/**
	 * @return Vector3 Returns center of the selection
	 *
	 * @throws RuntimeException If there is not enough positions set to calculate center.
	 */
	abstract public function center(): Vector3;

	/**
	 * Fills selection
	 *
	 * @throws RuntimeException If there is not enough positions set to fill selection.
	 */
	abstract public function fill(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult;

	/**
	 * Fills selection sides
	 *
	 * @throws RuntimeException If there is not enough positions set to fill selection.
	 */
	abstract public function outline(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult;

	/**
	 * Creates walls around the selection
	 *
	 * @throws RuntimeException If there is not enough positions set to make walls
	 */
	abstract public function walls(BlockIdentifierList $blockGenerator, ?BlockIdentifierList $mask = null): UpdateResult;

	/**
	 * Duplicates selection $count times in specified $direction
	 *
	 * @throws RuntimeException If there is not enough positions set to make walls
	 */
	abstract public function stack(int $count, int $direction): UpdateResult;

	/**
	 * Naturalizes the selection
	 *
	 * @throws RuntimeException If there is not enough positions set to naturalize selection.
	 */
	abstract public function naturalize(?BlockIdentifierList $mask = null): UpdateResult;

	/**
	 * Saves selection to the clipboard
	 *
	 * @throws RuntimeException If there is not enough positions set to fill selection.
	 */
	abstract public function saveToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult;

	/**
	 * Saves selection to the clipboard and fills it with air
	 *
	 * @throws RuntimeException If there is not enough positions set to cut selection
	 */
	abstract public function cutToClipboard(Vector3 $relativePosition, ?BlockIdentifierList $mask = null): UpdateResult;

	/**
	 * Saves selection as schematics
	 * @phpstan-param Closure(\czechpmdevs\buildertools\schematics\SchematicActionResult $result): void $callback
	 *
	 * @throws RuntimeException If there is not enough positions set to save schematics
	 */
	abstract public function saveToSchematic(string $name, Closure $callback, ?BlockIdentifierList $mask = null): void;

	/**
	 * Changes biome in the selection to target biomeId
	 * @see \pocketmine\data\bedrock\BiomeIds
	 *
	 * @throws RuntimeException If there is not enough positions set to change biome
	 */
	abstract public function changeBiome(int $biomeId): UpdateResult;

	/**
	 * Moves the selection in both memory and world
	 *
	 * @throws RuntimeException If there is not enough positions set to move area
	 */
	abstract public function move(int $xMotion, int $yMotion, int $zMotion): UpdateResult;

	/**
	 * @return World Returns world the selection is located in
	 *
	 * @throws RuntimeException If there is no world set in selection
	 */
	abstract public function getWorld(): World;

	/**
	 * Function to handle left click with Wand Axe / Hand
	 */
	abstract public function handleWandAxeBlockBreak(Position $position): void;

	/**
	 * Function to handle right click with Wand Axe / Hand
	 */
	abstract public function handleWandAxeBlockClick(Position $position): void;
}