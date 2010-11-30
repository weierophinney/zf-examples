<?php
/**
 * Setup autoloading
 */
spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');
    return include str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . '.php';
}, true, true);
