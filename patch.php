<?php

# Script which will generate plugin compatible with php 7.3

function removeDirectory(string $path) {
    foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
        if(is_file($file)) {
            unlink($file);
        } else {
            removeDirectory($file);
        }
    }

    @rmdir($path);
}

function scanDirectory(string $path): Generator {
    foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $folder) {
        if(is_file($folder)) {
            yield $folder;
        } else {
            foreach (scanDirectory($folder) as $subFolder) {
                yield $subFolder;
            }
        }
    }
}

function fixFile(string $content): string {
    $content = str_replace(["): self {", "): self;"], [") {", ");"], $content);
    
    $split = explode("\n", $content);
    foreach ($split as $index => $line) {
        $line = preg_replace('/' . preg_quote('public ') . '[\w]+?' . preg_quote(' $') . '/', 'public $', $line);
        $line = preg_replace('/' . preg_quote('private ') . '[\w]+?' . preg_quote(' $') . '/', 'private $', $line);
        $line = preg_replace('/' . preg_quote('protected ') . '[\w]+?' . preg_quote(' $') . '/', 'protected $', $line);
        $line = preg_replace('/' . preg_quote('static ') . '[\w]+?' . preg_quote(' $') . '/', 'static $', $line);
        $line = preg_replace('/' . preg_quote('public ?') . '[\w]+?' . preg_quote(' $') . '/', 'public $', $line);
        $line = preg_replace('/' . preg_quote('private ?') . '[\w]+?' . preg_quote(' $') . '/', 'private $', $line);
        $line = preg_replace('/' . preg_quote('protected ?') . '[\w]+?' . preg_quote(' $') . '/', 'protected $', $line);
        $line = preg_replace('/' . preg_quote('static ?') . '[\w]+?' . preg_quote(' $') . '/', 'static $', $line);


        $split[$index] = $line;
    }
    

    return implode("\n", $split);
}

echo "Generating BuilderTools for php version 7.3 ...\n";

$startTime = microtime(true);

$pluginPath = getcwd() . DIRECTORY_SEPARATOR . "BuilderTools";
$targetPath = getcwd() . DIRECTORY_SEPARATOR . "out" . DIRECTORY_SEPARATOR . "BuilderTools";

removeDirectory($targetPath);
foreach (scanDirectory($pluginPath) as $folder) {
    @mkdir(str_replace($pluginPath, $targetPath, dirname($folder)), 0777, true);
    file_put_contents(str_replace($pluginPath, $targetPath, $folder), fixFile(file_get_contents($folder)));
}

echo "Generated in " . round(microtime(true)-$startTime, 2) . " seconds!\n";

