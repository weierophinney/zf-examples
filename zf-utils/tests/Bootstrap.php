<?php
/*
 * E_STRICT compliance
 */
error_reporting( E_ALL | E_STRICT );

/*
 * Determine the root, library, and tests directories
 */
$root        = realpath(dirname(__DIR__));
$coreLibrary = "$root/library";
$coreTests   = "$root/tests";

/*
 * Prepend the library/ and tests/ directories to the include_path. This allows 
 * the tests to run out of the box and helps prevent loading other copies that 
 * might supersede this copy.
 */
$path = array(
    $coreLibrary,
    $coreTests,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

/**
 * Setup autoloading
 */
include __DIR__ . '/_autoload.php';

if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true &&
    version_compare(PHPUnit_Runner_Version::id(), '3.1.6', '>=')) {

    /*
     * Add library/ directory to the PHPUnit code coverage appear in the code 
     * coverage report and that all production code source whitelist. This has 
     * the effect that only production code source files files, even those that 
     * are not covered by a test yet, are processed.
     */
    PHPUnit_Util_Filter::addDirectoryToWhitelist($coreLibrary);

    /*
     * Omit from code coverage reports the contents of the tests directory
     */
    foreach (array('.php', '.phtml', '.csv', '.inc') as $suffix) {
        PHPUnit_Util_Filter::addDirectoryToFilter($coreTests, $suffix);
    }
    PHPUnit_Util_Filter::addDirectoryToFilter(PEAR_INSTALL_DIR);
    PHPUnit_Util_Filter::addDirectoryToFilter(PHP_LIBDIR);
}


/*
 * Unset global variables that are no longer needed.
 */
unset($root, $coreLibrary, $coreTests, $path);
