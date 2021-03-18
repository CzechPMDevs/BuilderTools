<?php

echo "Testing memory usage/speed for saving block lists.\n";

$cubeSize = 100;
$totalBlocks = pow($cubeSize, 3);
echo "Working with {$cubeSize}x{$cubeSize}x{$cubeSize} ($totalBlocks blocks) cube with random block ids and metas:\n\n";

ini_set("memory_limit", -1);

$array = [];
for($i = 0; $i < ($cubeSize ** 3); $i++) {
    $array[] = [
        mt_rand(0, 127),
        mt_rand(0, 15),
        mt_rand(-1000, 1000),
        mt_rand(0, 255),
        mt_rand(-1000, 1000)
    ];
}

echo "Data pre-generated!\n\n";

// FIRST
//$currentMemory = memory_get_usage();
//
//$firstTestStart = microtime(true);
//$firstTestArray = [];
//foreach ($array as [$id, $meta, $x, $y, $z]) {
//    $firstTestArray[(($x & 0xFFFFFFF) << 36) | (($y & 0xff) << 28) | ($z & 0xFFFFFFF)] = new Block($id, $meta);
//}
//$firstTestWriteFinish = microtime(true);
//foreach ($firstTestArray as $hash => $value) {
//    $id = $value->getId();
//    $meta = $value->getMeta();
//
//    $x = $hash >> 36;
//    $y = ($hash >> 28) & 0xff;
//    $z = ($hash & 0xFFFFFFF) << 36 >> 36;
//}
//$firstTestReadFinish = microtime(true);
//echo "First test (took " . round($firstTestReadFinish-$firstTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
//echo "Write time: " . round($firstTestWriteFinish - $firstTestStart, 4) . " sec\n";
//echo "Read time: " . round($firstTestReadFinish - $firstTestWriteFinish, 4) . " sec\n\n";

// SECOND
$currentMemory = memory_get_usage();

$secondTestStart = microtime(true);
$secondTestBuffer = "";
foreach ($array as [$id, $meta, $x, $y, $z]) {
    $secondTestBuffer .= chr($id) . chr($meta) . pack("q", (($x & 0xFFFFFFF) << 36) | (($y & 0xff) << 28) | ($z & 0xFFFFFFF));
}
$secondTestWriteFinish = microtime(true);
for($i = 0, $j = strlen($secondTestBuffer); $i < $j;) {
    $id = ord($secondTestBuffer[$i++]);
    $meta = ord($secondTestBuffer[$i++]);
    $hash = unpack("q", substr($secondTestBuffer,  $i, 8))[1];

    $i += 8;

    $x = $hash >> 36;
    $y = ($hash >> 28) & 0xff;
    $z = ($hash & 0xFFFFFFF) << 36 >> 36;
}

$secondTestReadFinish = microtime(true);
echo "Second test (took " . round($secondTestReadFinish-$secondTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
echo "Write time: " . round($secondTestWriteFinish - $secondTestStart, 4) . " sec\n";
echo "Read time: " . round($secondTestReadFinish - $secondTestWriteFinish, 4) . " sec\n\n";

// THIRD (LAST BP RELEASE)
$currentMemory = memory_get_usage();

$thirdTestStart = microtime(true);
$thirdTestArray = [];
foreach ($array as [$id, $meta, $x, $y, $z]) {
    $thirdTestArray[] = new Block2($id, $meta, $x, $y, $z);
}
$thirdTestWriteFinish = microtime(true);
foreach ($thirdTestArray as $block) {
    $id = $block->getId();
    $meta = $block->getMeta();
    $x = $block->getX();
    $y = $block->getY();
    $z = $block->getZ();
}

$thirdTestReadFinish = microtime(true);
echo "Third test (took " . round($thirdTestReadFinish-$thirdTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
echo "Write time: " . round($thirdTestWriteFinish - $thirdTestStart, 4) . " sec\n";
echo "Read time: " . round($thirdTestReadFinish - $thirdTestWriteFinish, 4) . " sec\n\n";

// FOURTH
$currentMemory = memory_get_usage();

$fourthTestStart = microtime(true);
$fourthTestArray = [];
foreach ($array as [$id, $meta, $x, $y, $z]) {
    $fourthTestBuffer[pack("q", (($x & 0xFFFFFFF) << 36) | (($y & 0xff) << 28) | ($z & 0xFFFFFFF))] = chr($id) . chr($meta);
}
$fourthTestWriteFinish = microtime(true);
foreach ($fourthTestArray as $index => $value) {
    $id = ord($value[0]);
    $meta = ord($value[1]);

    $hash = unpack("q", $index)[1];

    $x = $hash >> 36;
    $y = ($hash >> 28) & 0xff;
    $z = ($hash & 0xFFFFFFF) << 36 >> 36;
}

$fourthTestReadFinish = microtime(true);
echo "Fourth test (took " . round($fourthTestReadFinish-$fourthTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
echo "Write time: " . round($fourthTestWriteFinish - $fourthTestStart, 4) . " sec\n";
echo "Read time: " . round($fourthTestReadFinish - $fourthTestWriteFinish, 4) . " sec\n\n";

// FIFTH
$currentMemory = memory_get_usage();

$fifthTestStart = microtime(true);
$fifthTestArray = [];
foreach ($array as [$id, $meta, $x, $y, $z]) {
    $fifthTestBuffer[pack("q", (($x & 0xFFFFFFF) << 36) | (($y & 0xff) << 28) | ($z & 0xFFFFFFF))] = $id << 4 | $meta;
}
$fifthTestWriteFinish = microtime(true);
foreach ($fifthTestArray as $bin => $value) {
    $id = $value >> 4;
    $meta = $value & 0x0f;

    $hash = unpack("q", $bin);

    $x = $hash >> 36;
    $y = ($hash >> 28) & 0xff;
    $z = ($hash & 0xFFFFFFF) << 36 >> 36;
}

$fifthTestReadFinish = microtime(true);
echo "Fifth test (took " . round($fifthTestReadFinish-$fifthTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
echo "Write time: " . round($fifthTestWriteFinish - $fifthTestStart, 4) . " sec\n";
echo "Read time: " . round($fifthTestReadFinish - $fifthTestWriteFinish, 4) . " sec\n\n";

// SIXTH
$currentMemory = memory_get_usage();

$sixthTestStart = microtime(true);
$sixthTestArray1 = [];
$sixthTestArray2 = [];
foreach ($array as [$id, $meta, $x, $y, $z]) {
    $sixthTestArray1[] = (($x & 0xFFFFFFF) << 36) | (($y & 0xff) << 28) | ($z & 0xFFFFFFF);
    $sixthTestArray2[] = $id << 4 | $meta;
}

$sixthTestWriteFinish = microtime(true);
$size = count($sixthTestArray1);
for($i = 0; $i < $size; $i++) {
    $coords = $sixthTestArray1[$i];

    $x = $coords >> 36;
    $y = ($coords >> 28) & 0xff;
    $z = ($coords & 0xFFFFFFF) << 36 >> 36;

    $hash = $sixthTestArray2[$i];
    $id = $hash >> 4;
    $meta = $hash & 0xf;
}

$sixthTestReadFinish = microtime(true);
echo "Sixth test (took " . round($sixthTestReadFinish-$sixthTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
echo "Write time: " . round($sixthTestWriteFinish - $sixthTestStart, 4) . " sec\n";
echo "Read time: " . round($sixthTestReadFinish - $sixthTestWriteFinish, 4) . " sec\n\n";

// SEVENTH
$currentMemory = memory_get_usage();

$seventhTestStart = microtime(true);
$seventhTestArray1 = [];
$seventhTestArray2 = [];
foreach ($array as [$id, $meta, $x, $y, $z]) {
    $seventhTestArray1[] = pack("q", (($x & 0xFFFFFFF) << 36) | (($y & 0xff) << 28) | ($z & 0xFFFFFFF));
    $seventhTestArray2[] = $id << 4 | $meta;
}

$seventhTestWriteFinish = microtime(true);
$size = count($seventhTestArray1);
for($i = 0; $i < $size; $i++) {
    $coords = unpack("q", $sixthTestArray1[$i])[1];

    $x = $coords >> 36;
    $y = ($coords >> 28) & 0xff;
    $z = ($coords & 0xFFFFFFF) << 36 >> 36;

    $id = $seventhTestArray2[$i] >> 4;
    $meta = $seventhTestArray2[$i] & 0xf;
}

$seventhTestReadFinish = microtime(true);
echo "Seventh test (took " . round($seventhTestReadFinish-$seventhTestStart, 4) ." seconds): " . round((memory_get_usage() - $currentMemory) / 1000_000, 4) . "Mbit ram used\n";
echo "Write time: " . round($seventhTestWriteFinish - $seventhTestStart, 4) . " sec\n";
echo "Read time: " . round($seventhTestReadFinish - $seventhTestWriteFinish, 4) . " sec\n\n";

class Block {
    
    public int $id, $meta;
    
    public function __construct(int $id, int $meta) {
        $this->id = $id;
        $this->meta = $meta;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMeta(): int {
        return $this->meta;
    }
}

class Block2 {

    public int $id, $meta, $x, $y, $z;

    public function __construct(int $id, int $meta, int $x, int $y, int $z) {
        $this->id = $id;
        $this->meta = $meta;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMeta(): int {
        return $this->meta;
    }

    /**
     * @return int
     */
    public function getX(): int {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getZ(): int {
        return $this->z;
    }
}