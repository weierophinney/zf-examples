<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   ZendX
 * @package    Loader
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @category   ZendX
 * @package    Loader
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Loader
 */
class ZendX_Loader_StandardAutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Store original autoloaders
        $this->loaders = spl_autoload_functions();
        if (!is_array($this->loaders)) {
            // spl_autoload_functions does not return empty array when no
            // autoloaders registered...
            $this->loaders = array();
        }

        // Store original include_path
        $this->includePath = get_include_path();
    }

    public function tearDown()
    {
        // Restore original autoloaders
        $loaders = spl_autoload_functions();
        if (is_array($loaders)) {
            foreach ($loaders as $loader) {
                spl_autoload_unregister($loader);
            }
        }

        foreach ($this->loaders as $loader) {
            spl_autoload_register($loader);
        }

        // Restore original include_path
        set_include_path($this->includePath);
    }

    public function testFallbackAutoloaderFlagDefaultsToFalse()
    {
        $loader = new ZendX_Loader_StandardAutoloader();
        $this->assertFalse($loader->isFallbackAutoloader());
    }

    public function testFallbackAutoloaderStateIsMutable()
    {
        $loader = new ZendX_Loader_StandardAutoloader();
        $loader->setFallbackAutoloader(true);
        $this->assertTrue($loader->isFallbackAutoloader());
        $loader->setFallbackAutoloader(false);
        $this->assertFalse($loader->isFallbackAutoloader());
    }

    public function testPassingNonTraversableOptionsToSetOptionsRaisesException()
    {
        $loader = new ZendX_Loader_StandardAutoloader();

        $obj  = new stdClass();
        foreach (array(true, 'foo', $obj) as $arg) {
            try {
                $loader->setOptions(true);
                $this->fail('Setting options with invalid type should fail');
            } catch (InvalidArgumentException $e) {
                $this->assertContains('array or Traversable', $e->getMessage());
            }
        }
    }

    public function testPassingArrayOptionsPopulatesProperties()
    {
        $options = array(
            'namespaces' => array(
                'Zend\\'   => dirname(dirname(__FILE__)) . '/',
            ),
            'prefixes'   => array(
                'ZendX_'  => dirname(dirname(__FILE__)) . '/',
            ),
            'fallback_autoloader' => true,
        );
        $loader = new ZendX_Loader_TestAsset_StandardAutoloader();
        $loader->setOptions($options);
        $this->assertEquals($options['namespaces'], $loader->getNamespaces());
        $this->assertEquals($options['prefixes'], $loader->getPrefixes());
        $this->assertTrue($loader->isFallbackAutoloader());
    }

    public function testPassingTraversableOptionsPopulatesProperties()
    {
        $namespaces = new \ArrayObject(array(
            'Zend\\' => dirname(dirname(__FILE__)) . '/',
        ));
        $prefixes = new \ArrayObject(array(
            'ZendX_' => dirname(dirname(__FILE__)) . '/',
        ));
        $options = new \ArrayObject(array(
            'namespaces' => $namespaces,
            'prefixes'   => $prefixes,
            'fallback_autoloader' => true,
        ));
        $loader = new ZendX_Loader_TestAsset_StandardAutoloader();
        $loader->setOptions($options);
        $this->assertEquals((array) $options['namespaces'], $loader->getNamespaces());
        $this->assertEquals((array) $options['prefixes'], $loader->getPrefixes());
        $this->assertTrue($loader->isFallbackAutoloader());
    }

    public function testAutoloadsNamespacedClasses()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped();
        }
        $loader = new ZendX_Loader_StandardAutoloader();
        $loader->registerNamespace('ZendX\UnusualNamespace', dirname(__FILE__) . '/TestAsset');
        $loader->autoload('ZendX\UnusualNamespace\NamespacedClass');
        $this->assertTrue(class_exists('ZendX\UnusualNamespace\NamespacedClass', false));
    }

    public function testAutoloadsVendorPrefixedClasses()
    {
        $loader = new ZendX_Loader_StandardAutoloader();
        $loader->registerPrefix('ZendX_UnusualPrefix', dirname(__FILE__) . '/TestAsset');
        $loader->autoload('ZendX_UnusualPrefix_PrefixedClass');
        $this->assertTrue(class_exists('ZendX_UnusualPrefix_PrefixedClass', false));
    }

    public function testCanActAsFallbackAutoloader()
    {
        $loader = new ZendX_Loader_StandardAutoloader();
        $loader->setFallbackAutoloader(true);
        set_include_path(dirname(__FILE__) . '/TestAsset/' . PATH_SEPARATOR . $this->includePath);
        $loader->autoload('TestNamespace_FallbackCase');
        $this->assertTrue(class_exists('TestNamespace_FallbackCase', false));
    }

    public function testReturnsFalseForUnresolveableClassNames()
    {
        $loader = new ZendX_Loader_StandardAutoloader();
        $this->assertFalse($loader->autoload('Some\Fake\Classname'));
    }

    public function testRegisterRegistersCallbackWithSplAutoload()
    {
        $loader = new ZendX_Loader_StandardAutoloader();
        $loader->register();
        $loaders = spl_autoload_functions();
        $this->assertTrue(count($this->loaders) < count($loaders));
        $test = array_pop($loaders);
        $this->assertEquals(array($loader, 'autoload'), $test);
    }
}
