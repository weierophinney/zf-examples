<?php
/**
 * Utility for determining component dependencies
 *
 * This utility will scan a class file or tree of class files for 
 * component-level dependencies.
 *
 * Usage:
 *
 *     php scanDeps.php <path>
 * 
 * It will return a list of dependencies, one per line. If none are found, the 
 * message "No dependencies found" will be returned.
 */

namespace Zf\Util;

use RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

// Setup include_path and autoloading
set_include_path(implode(PATH_SEPARATOR, array(
    __DIR__ . '/../library',
    get_include_path(),
)));
spl_autoload_register(function($class) {
    $class = ltrim($class, '\\');
    $file  = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . '.php';
    return include($file);
});

// No arguments? 
if ($argc <= 1) {
    echo $argv[0] . ": requires a filename or path as an argument\n";
    exit(1);
}

// Invalid arguments? 
$path = $argv[1];
if (!is_file($path) && !is_dir($path)) {
    echo $argv[0] . ": requires a valid filename or path as an argument\n";
    exit(1);
}

// File provided
if (is_file($path)) {
    $deps = Dependencies::getForFile($path);
    if (!count($deps)) {
        echo "No dependencies found\n";
        exit(0);
    }
    foreach ($deps as $dep) {
        echo $dep, "\n";
    }
    exit(0);
}

// Directory provided
$deps = array();
$it   = new RecursiveDirectoryIterator($path);
foreach (new RecursiveIteratorIterator($it) as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $filePath = $file->getRealPath();
    if ('.php' != substr($filePath, -4)) {
        continue;
    }

    $deps += Dependencies::getForFile($filePath);
}

if (!count($deps)) {
    echo "No dependencies found\n";
    exit(0);
}

foreach (array_unique($deps) as $dep) {
    echo $dep, "\n";
}
exit(0);
