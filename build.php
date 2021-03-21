<?php

declare(strict_types=1);

/**
 * Build script for BuilderTools
 */

// For example C:/pmmp-server/plugins/BuilderTools.phar
const CUSTOM_OUTPUT_PATH = "C:\Users\stehl\Desktop\pmmp\plugins\BuilderTools.phar";
const COMPRESS_FILES = true;
const COMPRESSION = Phar::GZ;

$startTime = microtime(true);

// Input & Output directory...
$from = getcwd() . DIRECTORY_SEPARATOR;
$to = getcwd() . DIRECTORY_SEPARATOR . "out" . DIRECTORY_SEPARATOR . "BuilderTools" . DIRECTORY_SEPARATOR;

@mkdir($to, 0777, true);

// Clean output directory...
cleanDirectory($to);

// Copying new files...
copyDirectory($from . "src", $to . "src");
copyDirectory($from . "resources", $to . "resources");

$description = yaml_parse_file($from . "plugin.yml");
yaml_emit_file($to . "plugin.yml", $description);

// Defining output path...
$outputPath = CUSTOM_OUTPUT_PATH == "" ? getcwd() . DIRECTORY_SEPARATOR . "out" . DIRECTORY_SEPARATOR . "BuilderTools_v{$description["version"]}_dev.phar" : CUSTOM_OUTPUT_PATH;
@unlink($outputPath);

// Generate phar
$phar = new Phar($outputPath);
$phar->buildFromDirectory($to);

if(COMPRESS_FILES) {
    $phar->compressFiles(COMPRESSION);
}

printf("Plugin built in %s seconds! Output path: %s\n", round(microtime(true) - $startTime, 3), $outputPath);

function copyDirectory(string $from, string $to): void {
    mkdir($to, 0777, true);

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    /** @var SplFileInfo $fileInfo */
    foreach ($files as $fileInfo) {
        $target = str_replace($from, $to, $fileInfo->getPathname());
        if($fileInfo->isDir()) {
            mkdir($target, 0777, true);
        } else {
            $contents = file_get_contents($fileInfo->getPathname());
            preProcess($contents);
            file_put_contents($target, $contents);
        }
    }
}

function cleanDirectory(string $directory): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    /** @var SplFileInfo $fileInfo */
    foreach ($files as $fileInfo) {
        if($fileInfo->isDir()) {
            rmdir($fileInfo->getPathname());
        } else {
            unlink($fileInfo->getPathname());
        }
    }
}

function preProcess(string &$file): void {
    if(!defined("replacements")) {
        $patterns = [
            'Math::lengthSquared2d({%1}, {%2})' => '({%1} ** 2) + ({%2} ** 2)',
            'Math::lengthSquared3d({%1}, {%2}, {%3})' => '({%1} ** 2) + ({%2} ** 2) + ({%3} ** 2)',
        ];

        $replacements = [];

        foreach ($patterns as $key => $value) {
            $key = "#" . str_replace(["(", ")"], ["\(", "\)"], $key) . "#";

            /** @noinspection PhpStatementHasEmptyBodyInspection */
            for($i = 0; strpos($key, "{%" . (++$i) ."}") !== false;);

            for($j = 1; $j < $i; ++$j) {
                $key = str_replace("{%$j}", "(.*?)", $key);
                $value = str_replace("{%$j}", "\$$j", $value);
            }

            $replacements[$key] = $value;
        }

        define("replacements", $replacements);
    }

    $file = preg_replace(array_keys(replacements), array_values(replacements), $file);
}