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

/**
 * Script generating resources for schematics loading
 *
 * Requires
 * 	- composer installed with PocketMine package
 * 	- blocks.json from GeyserMC (https://github.com/GeyserMC/mappings/blob/master/blocks.json)
 */

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\utils\AssumptionFailedError;
use Webmozart\PathUtil\Path;
use const pocketmine\BEDROCK_DATA_PATH;

chdir("../");

define("pocketmine\BEDROCK_DATA_PATH", "vendor/pocketmine/bedrock-data/");

require "vendor/autoload.php";

/** @var array<string, int> $bedrockStatesMap */
$bedrockStatesMap = [];
/** @var array<string, int> $javaStatesMap */
$javaStatesMap = [];

$legacyIdMapFile = file_get_contents(BEDROCK_DATA_PATH . "block_id_map.json");
if($legacyIdMapFile === false) {
	throw new AssumptionFailedError("Missing required resource file");
}

/** @var array<string, int> $legacyIdMap */
$legacyIdMap = json_decode($legacyIdMapFile, true);

$legacyBlockStatesFile = file_get_contents(Path::join(BEDROCK_DATA_PATH, "r12_to_current_block_map.bin"));
if($legacyBlockStatesFile === false) {
	throw new AssumptionFailedError("Missing required resource file");
}
$legacyStateMapReader = PacketSerializer::decoder($legacyBlockStatesFile, 0, new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
$nbtReader = new NetworkNbtSerializer();

while(!$legacyStateMapReader->feof()) {
	$id = $legacyStateMapReader->getString();
	$meta = $legacyStateMapReader->getLShort();

	$offset = $legacyStateMapReader->getOffset();
	$state = $nbtReader->read($legacyStateMapReader->getBuffer(), $offset)->mustGetCompoundTag();
	$legacyStateMapReader->setOffset($offset);

	$legacyId = $legacyIdMap[$id] ?? null;
	if($legacyId === null) {
		throw new RuntimeException("No legacy ID matches to $id");
	}

	if($meta > 15) { // Meta > 15 is not supported yet
		$meta = 0;
	}

	$bedrockStatesMap[serializeBlockNbt($state)] = $legacyId << 4 | $meta;
}

$java2BedrockStatesFile = file_get_contents("blocks.json");
if(!$java2BedrockStatesFile) {
	throw new AssumptionFailedError("Missing required resource file");
}

$found = $checked = 0;
foreach(json_decode($java2BedrockStatesFile, true) as $javaId => $bedrockData) {
	++$checked;

	$state = serializeBlockArray($bedrockData);
	if(array_key_exists($state, $bedrockStatesMap)) {
		$javaStatesMap[$javaId] = $bedrockStatesMap[$state];
		++$found;
		continue;
	}

	// Update block
	$javaStatesMap[$javaId] = 248 << 4;

	echo "Bedrock state $state was not found for $javaId\n";
}

echo "Found $found of $checked block states.\n";

asort($bedrockStatesMap);
file_put_contents("bedrock_block_states_map.json", json_encode($bedrockStatesMap));

asort($javaStatesMap);
file_put_contents("java_block_states_map.json", json_encode($javaStatesMap));


/**
 * @param array<string, mixed> $tag
 */
function serializeBlockArray(array $tag): string {
	$serialized = $tag["bedrock_identifier"];
	if(array_key_exists("bedrock_states", $tag)) {
		$data = [];
		foreach($tag["bedrock_states"] as $key => $val) {
			if(is_bool($val)) {
				$val = (int)$val;
			}
			$data[] = "$key=$val";
		}
		$serialized .= "[" . implode(",", $data) . "]";
	}
	return $serialized;
}

function serializeBlockNbt(CompoundTag $tag): string {
	$serialized = $tag->getString("name");
	if(($states = $tag->getCompoundTag("states")) !== null && $states->count() != 0) {
		$data = [];
		foreach($states->getValue() as $key => $val) {
			$val = $val->getValue();
			if(is_bool($val)) {
				$val = (int)$val;
			}
			$data[] = "$key=$val";
		}
		$serialized .= "[" . implode(",", $data) . "]";
	}
	return $serialized;
}


