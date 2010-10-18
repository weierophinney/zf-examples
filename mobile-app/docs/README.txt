README
======

This app is meant to be a demonstration of Zend Framework 1.11.0's
mobile functionality.

REQUIREMENTS
============

WURFL PHP API:
 * Download the WURFL PHP API (and note where you download it):
    http://sourceforge.net/projects/wurfl/files/WURFL%20PHP/1.1/wurfl-php-1.1.tar.gz/download
 * Descend into the library directory:
    cd /path/to/mobile-app/library
 * Extract the WURFL library:
    tar xzvf /path/to/wurfl-php-1.1.tar.gz
 * Inflating the library creates the following hierarchy:
    library
    |-- wurfl-php-1.1
    |   |-- COPYING
    |   |-- docs
    |   |-- examples
    |   |-- README
    |   |-- tests
    |   `-- WURFL
 * Return to the project root directory
 * Create the data and cache directory:
    mkdir -p data/wurfl/cache
 * Copy the WURFL data, which consists of a ZIP file and one or more
   XML patch files, into the data directory:
    cp library/wurfl-php-1.1/tests/resources/wurfl-latest.zip data/wurfl/
    cp library/wurfl-php-1.1/tests/resources/web_browsers_patch.xml data/wurfl/
 * Create the WURFL configuration file at (application/configs/wurfl-config.php) to read as follows:
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
 * Make sure the cache directory is world writable (or at least writable
   by your web user):
    chmod -R o+rwX data/
 * Update your application/configs/application.ini to add the following
   lines to the "[production]" section:
    resources.useragent.wurflapi.wurfl_api_version = "1.1"
    resources.useragent.wurflapi.wurfl_lib_dir = APPLICATION_PATH "/../library/wurfl-php-1.1/WURFL/"
    resources.useragent.wurflapi.wurfl_config_file = APPLICATION_PATH "/configs/wurfl-config.php"
