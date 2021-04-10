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

namespace czechpmdevs\buildertools\schematics\format;

use czechpmdevs\buildertools\blockstorage\BlockArray;
use czechpmdevs\buildertools\editors\Fixer;
use czechpmdevs\buildertools\schematics\SchematicException;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use Throwable;
use function chr;
use function ord;
use function str_repeat;
use function strtolower;

class MCEditSchematic implements Schematic {

    public const MATERIALS_CLASSIC = "Classic";
    public const MATERIALS_BEDROCK = "Pocket";
    public const MATERIALS_ALPHA = "Alpha";

    /**
     * @throws SchematicException
     */
    public function load(string $rawData): BlockArray {
        $nbt = (new BigEndianNBTStream())->readCompressed($rawData);
        if(!$nbt instanceof CompoundTag) {
            throw new SchematicException("NBT root must be compound tag");
        }

        $this->readDimensions($nbt, $width, $height, $length);
        $this->readBlockData($nbt, $blocks, $data);
        $this->readMaterials($nbt, $materials);

        $blockArray = new BlockArray();

        $i = 0;
        for($y = 0; $y < $height; ++$y) {
            for($z = 0; $z < $length; ++$z) {
                for($x = 0; $x < $width; ++$x) {
                    $id = ord($blocks[$i]);
                    $meta = ord($data[$i]);

                    $blockArray->addBlockAt($x, $y, $z, $id, $meta);
                    ++$i;
                }
            }
        }

        if($materials == self::MATERIALS_CLASSIC || $materials == self::MATERIALS_ALPHA) {
            $fixer = Fixer::getInstance();

            foreach ($blockArray->blocks as &$fullBlock) {
                $fixer->convertJavaToBedrockId($fullBlock);
            }
        }

        return $blockArray;
    }

    /**
     * @throws SchematicException
     */
    private function readDimensions(CompoundTag $nbt, ?int &$xSize, ?int &$ySize, ?int &$zSize): void {
        if(
            !$nbt->hasTag("Width", ShortTag::class) ||
            !$nbt->hasTag("Height", ShortTag::class) ||
            !$nbt->hasTag("Length", ShortTag::class)
        ) {
            throw new SchematicException("NBT does not contain Dimension vector");
        }

        $xSize = $nbt->getShort("Width");
        $ySize = $nbt->getShort("Height");
        $zSize = $nbt->getShort("Length");
    }

    /**
     * @throws SchematicException
     */
    private function readBlockData(CompoundTag $nbt, ?string &$blocks, ?string &$data): void {
        if(
            !$nbt->hasTag("Blocks", ByteArrayTag::class) ||
            !$nbt->hasTag("Data", ByteArrayTag::class)
        ) {
            throw new SchematicException("NBT does not contains Block information");
        }

        $blocks = $nbt->getByteArray("Blocks");
        $data = $nbt->getByteArray("Data");
    }

    private function readMaterials(CompoundTag $nbt, ?string &$materials): void {
        if(strtolower($nbt->getString("Materials", self::MATERIALS_CLASSIC)) == strtolower(self::MATERIALS_BEDROCK)) {
            $materials = self::MATERIALS_BEDROCK;
            return;
        }

        $materials = self::MATERIALS_CLASSIC;
    }

    /**
     * @throws SchematicException
     */
    public function save(BlockArray $blockArray): string {
        $nbt = new CompoundTag();

        $sizeData = $blockArray->getSizeData();

        $width = (($sizeData->maxX - $sizeData->minX) + 1);
        $height = (($sizeData->maxY - $sizeData->minY) + 1);
        $length = (($sizeData->maxZ - $sizeData->minZ) + 1);

        $xz = $width * $length;
        $totalSize = $xz * $height;

        $blocks = $data = str_repeat(chr(0), $totalSize);
        while ($blockArray->hasNext()) {
            $blockArray->readNext($x, $y, $z, $id, $meta);
            $key = $z + ($width * $x) + ($xz * $y);

            $blocks[$key] = chr($id);
            $data[$key] = chr($meta);
        }

        $this->writeDimensions($nbt, $width, $height, $length);

        /**
         * @phpstan-var string $blocks
         * @phpstan-var string $data
         */
        $this->writeBlockData($nbt, $blocks, $data);
        $this->writeMaterials($nbt, self::MATERIALS_BEDROCK);

        $rawData = (new BigEndianNBTStream())->writeCompressed($nbt);
        if($rawData === false) {
            throw new SchematicException("Could not compress nbt");
        }

        return $rawData;
    }

    private function writeDimensions(CompoundTag $nbt, int $xSize, int $ySize, int $zSize): void {
        $nbt->setShort("Width", $xSize);
        $nbt->setShort("Height", $ySize);
        $nbt->setShort("Length", $zSize);
    }

    private function writeBlockData(CompoundTag $nbt, string $blocks, string $data): void {
        $nbt->setByteArray("Blocks", $blocks);
        $nbt->setByteArray("Data", $data);
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function writeMaterials(CompoundTag $nbt, string $materials): void {
        $nbt->setString("Materials", $materials);
    }

    public static function getFileExtension(): string {
        return "schematic";
    }

    public static function validate(string $rawData): bool {
        try {
            $nbt = (new BigEndianNBTStream())->readCompressed($rawData);
            if(!$nbt instanceof CompoundTag) {
                return false;
            }

            // MCEdit
            if(
                $nbt->hasTag("Width", ShortTag::class) &&
                $nbt->hasTag("Height", ShortTag::class) &&
                $nbt->hasTag("Length", ShortTag::class) &&
                $nbt->hasTag("Blocks", ByteArrayTag::class) &&
                $nbt->hasTag("Data", ByteArrayTag::class)
            ) {
                return true;
            }

            return false;
        } catch (Throwable $ignore) {
            return false;
        }
    }
}