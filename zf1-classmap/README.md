ZF2 Autoloaders in ZF1 and/or PHP 5.2 Projects
==============================================

This example/subproject is a backport of the ZF2 autoloaders to PHP 5.2. Instead
of namespaces, class prefixes are used, and all PHP 5.3-specific implementation
details (closures in tests, namespaces, `__DIR__` magic constant, etc.) were
refactored to use standard PHP functionality available before PHP 5.3.

Additionally, the classes are under a `ZendX_` prefix to prevent collisions (if
any) with ZF code and to allow installing side-by-side with a standard ZF
distribution.

FEATURES
--------

* PSR-0-compliant `include_path` autoloading
* PSR-0-compliant per-prefix or namespace autoloading
* Classmap autoloading, including classmap generation
* Autoloader factory for loading several autoloader strategies at once

USAGE
-----

### SplAutoloader Interface ###

Each autoloader shipped implements the same interface,
`ZendX_Loader_SplAutoloader`. This interface is as follows:

    interface ZendX_Loader_SplAutoloader
    {
        public function __construct($options = null);
        public function setOptions($options);
        public function autoload($class);
        public function register();
    }

The important pieces to remember is that autoloaders should typically be
configurable (and accept configuration via the constructor), the autoload
functionality should be defined via the `autoload()` method, and each should
register with `spl_autoload_register()` when `register()` is called.

Any autoloader that implements the above interface will work with the
AutoloaderFactory.

### StandardAutoloader ###

The StandardAutoloader is a PSR-0-compliant autoloader. It features three use
cases:

* `include_path`-based autoloading
* Autoloading from specific namespace/directory pairs
* Autoloading from specific vendor prefix/directory pairs

The above may be combined so that the same autoloader instance can be used to
autoload from a variety of PHP namespaces or vendor prefixes.

### Examples ###

Example usage:

    require_once 'path/to/library/ZendX/Loader/StandardAutoloader.php';
    $loader = new ZendX_Loader_StandardAutoloader(array(
        'prefixes' => array(
            'Zend' => 'path/to/library/Zend',
            'Phly' => 'path/to/vendor/Phly',
        ),
        'namespaces' => array(
            'My' => 'path/to/library/My',
        ),
        'fallback_autoloader' => true,
    ));
    $loader->register(); // register with spl_autoload_register()

The above example will:

* Load classes with the vendor prefix "Zend_" from "path/to/library/Zend", and
  from vendor prefix "Phly_" from "path/to/vendor/Phly".
* Load classes with the PHP 5.3 namespace "My" from "path/to/library/My".
* Attempt to load any code with un-registered vendor prefixes and/or namespaces
  from the `include_path`.

The above example shows passing configuration to the constructor; however, you
can do all of the above programmatically as well:

    require_once 'path/to/library/ZendX/Loader/StandardAutoloader.php';
    $loader = new ZendX_Loader_StandardAutoloader();
    $loader->registerPrefix('Zend', 'path/to/library/Zend')
           ->registerPrefix('Phly', 'path/to/vendor/Phly')
           ->registerNamespace('My', 'path/to/library/My')
           ->setFallbackAutoloader(true);
    $loader->register(); // register with spl_autoload_register()

When registering multiple prefixes or namespaces, you may also use the
`registerPrefixes()` and `registerNamespaces()` variants, which take arrays of
prefix/namespace keys paired with directory values.

    $loader->registerPrefixes(array(
        'Zend' => 'path/to/library/Zend',
        'Phly' => 'path/to/vendor/Phly',
    ));

When resolving classes, namespaces or prefixes are searched in the order in
which they are registered. As such, you should register from most specific to
least specific if registering multiple namespaces/prefixes with a common root.
Otherwise, register from most used to least used to ensure the least number of
lookups.

ClassMapAutoloader
------------------

The ClassMapAutoloader utilizes class maps to do its work; a class map is simply
an associative array with class names for keys and filesystem locations for
values. You may pass it explicit arrays (or Traversable objects), or the
filesystem location of a PHP file that returns an array.

Class maps are perhaps the fastest possible way to autoload classes, and provide
the least possible liklihood of errors in resolution. They also work very well
with PHP's opcode and realpath caches.

### Examples ###

Consider the following classmap in `.classmap.php`:

    <?php
    return array(
        'My\Namespaced\ComponentClass' => __DIR__ .  '/Namespaced/ComponentClass.php',
        'My_Prefixed_ComponentClass' => __DIR__ .  '/Prefixed/ComponentClass.php',
    );

This may then be passed to the ClassMapAutoloader:

    require_once 'path/to/library/ZendX/Loader/ClassMapAutoloader.php';
    $loader = new ZendX_Loader_ClassMapAutoloader(__DIR__ . '/.classmap.php');
    $loader->register();

    $ns = new My\Namespaced\ComponentClass();
    $pr = new My_Prefixed_ComponentClass();
    $er = new My_Unknown_Class(); // error - not found!

Alternately, it can be passed to the `registerAutoloadMap()` method:

    $loader->registerAutoloadMap(__DIR__ . '/.classmap.php');

You may also pass multiple maps at once:

    $loader = new ZendX_Loader_ClassMapAutoloader(array(
        __DIR__ . '/../library/.classmap.php',
        __DIR__ . '/../application/.classmap.php',
    ));

    // or

    $loader->registerAutoloadMaps(array(
        __DIR__ . '/../library/.classmap.php',
        __DIR__ . '/../application/.classmap.php',
    ));

Explicit maps are passed to either the constructor or `registerAutoloadMap()`:

    $loader = new ZendX_Loader_ClassMapAutoloader(array(
        'My\Namespaced\ComponentClass' => __DIR__ .  '/Namespaced/ComponentClass.php',
        'My_Prefixed_ComponentClass' => __DIR__ .  '/Prefixed/ComponentClass.php',
    ));

    // or

    $loader->registerAutoloadMap(array(
        'My\Namespaced\ComponentClass' => __DIR__ .  '/Namespaced/ComponentClass.php',
        'My_Prefixed_ComponentClass' => __DIR__ .  '/Prefixed/ComponentClass.php',
    ));

Maps are merged with whatever maps are already registered; the last registered
definition for a class wins. This allows you to override the location of class
if desired.

### Creating Class Maps ###

Maintaining class maps can be boring and prone to error. As such, a tool is
provided, `bin/classmap_generator.php`. 

> To run this tool, please be sure to have a recent Zend Framework installation
> on your path

The tool provides help options (`-h` or `--help`) that describe usage. Basic
usage is simple:

    prompt> php path/to/bin/classmap_generator.php -w
    Creating class file map for library in '/var/www/project/library'...
    Wrote classmap file to '/var/www/project/library/.classmap.php'

The "-w" switch tells the tool to overwrite the classmap file if one already
exists. We recommend that you tell your IDE to execute this command when new
files are created under a given tree, create a continuous integration task to
build it, or create a deployment task to execute it.

AutoloaderFactory
-----------------

The AutoloaderFactory can be used to configure autoloader instances and register
them with `spl_autoload_register()`; additionally, it can be used to initialize
many autoloading strategies at once. In particular, this is useful for providing
rapid application development advantages during early-stage development using
the StandardAutoloader, and run-time advantages during deployment using the
ClassMapAutoloader.

In all cases, autoloaders must implement `ZendX_Loader_SplAutoloader`.

### Usage ###

Usage is straight-forward: pass an array of autoloader classname keys with an
array of configuration. Autoloaders are registered in the order passed, so
register the method you want to use to match first. In most cases, the first
autoloader should be a ClassMapAutoloader, and the last a StandardAutoloader
configured to act as a fallback autoloader.

    require_once 'path/to/library/ZendX/Loader/AutoloaderFactory.php';
    ZendX_Loader_AutoloaderFactory::factory(array(
        'ZendX_Loader_ClassMapAutoloader' => array(
            __DIR__ . '/library/.classmap.php',
        ),
        'ZendX_Loader_StandardAutoloader' => array(
            'prefixes' => array(
                'Zend' => 'path/to/library/Zend',
            ),
            'namespaces' => array(
                'My' => 'path/to/library/My',
            ),
            'fallback_autoloader' => true,
        ),
    ));

> The factory will attempt to autoload the specified autoloaders from the
> `include_path`; if any autoloaders specified are not on the `include_path`,
> you should call `require_once` with their path prior to execution.

When the factory method executes, it will load and initialize each autoloader
with the specified options, and then execute the `register()` method, ensuring
that autoloading is now in play.

FEEDBACK and QUESTIONS
----------------------

If you have feedback and/or questions, please provide this in the
zf-contributors mailing list (see http://framework.zend.com/archives for
details).
