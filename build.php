<?php

# Build script for BuilderTools

$startTime = microtime(true);

$path = getcwd() . DIRECTORY_SEPARATOR . "out" . DIRECTORY_SEPARATOR;
@mkdir($path);

$pluginPath = $path . "BuilderTools" . DIRECTORY_SEPARATOR;
$description = (array)yaml_parse_file($pluginPath . "plugin.yml");

$outputPath = $path . "BuilderTools_v{$description["version"]}_dev.phar";
@unlink($outputPath);

$phar = new Phar($outputPath);
$phar->buildFromDirectory($pluginPath);

echo "Plugin built in " . ((string)round(microtime(true)-$startTime, 3)) . " seconds! Output path: $outputPath\n";