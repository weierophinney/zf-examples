<?php
/**
 * Setup autoloading
 */
function ZendXTest_Autoloader($class) 
{
    $file = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class) . ".php";
    return include_once $file;
}
spl_autoload_register('ZendXTest_Autoloader', true, true);

