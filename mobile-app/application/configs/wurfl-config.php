<?php
$resourcesDir            = dirname(__FILE__) . '/../../data/wurfl/';
    
$wurfl['main-file']      = $resourcesDir  . 'wurfl-latest.zip';
$wurfl['patches']        = array($resourcesDir . 'web_browsers_patch.xml');

$persistence['provider'] = 'file';
$persistence['dir']      = $resourcesDir . '/cache/';

$cache['provider']       = null;
    
$configuration['wurfl']       = $wurfl;
$configuration['persistence'] = $persistence; 
$configuration['cache']       = $cache;
