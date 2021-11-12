<?php

declare(strict_types=1);

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

namespace czechpmdevs\buildertools\editors;

use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use function array_key_exists;
use function json_decode;

class Fixer {
	use SingletonTrait;

	// Script to generate this here: https://gist.github.com/VixikHD/5e7e1874298b548df3d190d95c0ed577
	private const BLOCK_FIX_DATA = '{"2000":2512,"2001":2513,"2002":2514,"2003":2515,"2004":2516,"2005":2517,"2006":2518,"2007":2519,"2008":2520,"2009":2521,"2010":2522,"2011":2523,"2012":2524,"2013":2525,"2014":2526,"2015":2527,"2016":2528,"2017":2529,"2018":2530,"2019":2531,"2020":2532,"2021":2533,"2022":2534,"2023":2535,"2024":2536,"2025":2537,"2026":2538,"2027":2539,"2028":2540,"2029":2541,"2030":2542,"2031":2543,"2528":2528,"2656":1520,"3008":1360,"3024":1361,"3040":1362,"3056":1363,"3072":1364,"3088":1365,"3168":3328,"3184":3840,"3232":3216,"3264":3216,"3328":3168,"4016":3776,"4017":3777,"4018":3778,"4019":3779,"4020":3780,"4021":3781,"4022":3782,"4023":3783,"4024":3784,"4025":3785,"4026":3786,"4027":3787,"4028":3788,"4029":3789,"4030":3790,"4031":3791,"4032":3792,"4033":3793,"4034":3794,"4035":3795,"4036":3796,"4037":3797,"4038":3798,"4039":3799,"4040":3800,"4041":3801,"4042":3802,"4043":3803,"4044":3804,"4045":3805,"4046":3806,"4047":3807,"1520":3856,"1521":3857,"1522":3858,"1523":3859,"1524":3860,"1525":3861,"1526":3862,"1527":3863,"1528":3864,"1529":3865,"1530":3866,"1531":3867,"1532":3868,"1533":3869,"1534":3870,"1535":3871,"1536":1539,"1537":1538,"1538":1537,"1539":1536,"1540":1547,"1541":1546,"1542":1545,"1543":1544,"1544":1543,"1545":1542,"1546":1541,"1547":1540,"1548":1551,"1549":1550,"1550":1549,"1551":1548,"2672":2675,"2673":2674,"2674":2673,"2675":2672,"2676":2683,"2677":2682,"2678":2681,"2679":2680,"2680":2679,"2681":2678,"2682":2677,"2683":2676,"2684":2687,"2685":2686,"2686":2685,"2687":2684,"1232":1232,"1233":1237,"1234":1236,"1235":1235,"1236":1234,"1237":1233,"1238":1232,"1239":1247,"1240":1246,"1241":1245,"1242":1244,"1243":1243,"1244":1232,"1245":1247,"1246":1246,"1247":1245,"2288":2288,"2289":2293,"2290":2292,"2291":2291,"2292":2290,"2293":2289,"2294":2288,"2295":2303,"2296":2302,"2297":2301,"2298":2300,"2299":2299,"2300":2288,"2301":2303,"2302":2302,"2303":2301,"711":710,"719":718,"2896":2497}';

	/** @var int[] */
	private array $fullBlockFixData;

	protected function __construct() {
		/** @var int[] $fullBlockFixData */
		$fullBlockFixData = (array)json_decode(Fixer::BLOCK_FIX_DATA, true);
		$this->fullBlockFixData = $fullBlockFixData;
	}

	public function convertJavaToBedrockId(int &$fullBlock): bool {
		if(!array_key_exists($fullBlock, $this->fullBlockFixData)) {
			return false;
		}

		$fullBlock = $this->fullBlockFixData[$fullBlock];
		return true;
	}

	public function convertJavaToBedrockChunk(Chunk $chunk, int $maxY = 256): bool {
		$hasChanged = false;

		/** @var int|null $currentY */
		$currentY = null;
		/** @var SubChunk $subChunk */
		$subChunk = null;

		for($y = 0; $y < $maxY; ++$y) {
			if($currentY === null || $y >> 4 != $currentY) {
				$currentY = $y >> 4;
				$subChunk = $chunk->getSubChunk($y >> 4);

				if($subChunk->isEmptyFast()) {
					continue;
				}
			}

			for($x = 0; $x < 16; ++$x) {
				for($z = 0; $z < 16; ++$z) {
					$fullBlock = $subChunk->getFullBlock($x, $y & 0xf, $z);
					if($this->convertJavaToBedrockId($fullBlock)) {
						$subChunk->setFullBlock($x, $y & 0xf, $z, $fullBlock);

						$hasChanged = true;
					}
				}
			}
		}

		return $hasChanged;
	}
}
