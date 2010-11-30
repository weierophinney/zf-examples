<?php
/**
 * Simple script to scan PHP files for the string "\Zend" *not* occurring in a
 * commented line.
 */

$dir     = new RecursiveDirectoryIterator('/home/matthew/git/zf-standard/library/Zend');
$found   = 0;
$scanned = 0;
foreach (new RecursiveIteratorIterator($dir) as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getRealPath();
    if ('.php' != substr($path, -4)) {
        continue;
    }

    ++$scanned;
    $contents = file_get_contents($path);
    $lines = preg_split('/(\r\n|\r|\n)/', $contents);
    foreach ($lines as $line) {
        if (strstr($line, '\\Zend')) {
            if (preg_match('#^\s*(/\*)#', $line)) {
                // It's a comment; ignore
                continue;
            }

            // Otherwise, we found a match; echo the path and end the loop
            ++$found;
            echo $path, "\n";
            break;
        }
    }
}
printf("Scanned %d files; matched %d\n", $scanned, $found);
