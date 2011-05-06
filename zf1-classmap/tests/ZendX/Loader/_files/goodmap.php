<?php
$ds       = DIRECTORY_SEPARATOR;
$basePath = realpath(dirname(__FILE__) . "$ds..");
return array(
    'ZendX_Loader_StandardAutoloaderTest' => $basePath . $ds . 'StandardAutoloaderTest.php',
    'ZendX_Loader_ClassMapAutoloaderTest' => $basePath . $ds . 'ClassMapAutoloaderTest.php',
);
