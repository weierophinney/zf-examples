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
 * @category   Zend
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
class ZendX_Loader_ClassMapAutoloaderTest extends PHPUnit_Framework_TestCase
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

        $this->loader = new ZendX_Loader_ClassMapAutoloader();
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

    public function testRegisteringNonExistentAutoloadMapRaisesInvalidArgumentException()
    {
        $dir = dirname(__FILE__) . '__foobar__';
        $this->setExpectedException('InvalidArgumentException');
        $this->loader->registerAutoloadMap($dir);
    }

    public function testValidMapFileNotReturningMapRaisesInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->loader->registerAutoloadMap(dirname(__FILE__) . '/_files/badmap.php');
    }

    public function testAllowsRegisteringArrayAutoloadMapDirectly()
    {
        $map = array(
            'ZendX_Loader_Exception' => dirname(__FILE__) . '/../../../library/ZendX/Loader/Exception.php',
        );
        $this->loader->registerAutoloadMap($map);
        $test = $this->loader->getAutoloadMap();
        $this->assertSame($map, $test);
    }

    public function testRegisteringValidMapFilePopulatesAutoloader()
    {
        $this->loader->registerAutoloadMap(dirname(__FILE__) . '/_files/goodmap.php');
        $map = $this->loader->getAutoloadMap();
        $this->assertTrue(is_array($map));
        $this->assertEquals(2, count($map));
    }

    public function testRegisteringMultipleMapsMergesThem()
    {
        $map = array(
            'ZendX_Loader_Exception' => dirname(__FILE__) . '/../../../library/ZendX/Loader/Exception.php',
            'ZendX_Loader_StandardAutoloaderTest' => 'some/bogus/path.php',
        );
        $this->loader->registerAutoloadMap($map);
        $this->loader->registerAutoloadMap(dirname(__FILE__) . '/_files/goodmap.php');

        $test = $this->loader->getAutoloadMap();
        $this->assertTrue(is_array($test));
        $this->assertEquals(3, count($test));
        $this->assertNotEquals($map['ZendX_Loader_StandardAutoloaderTest'], $test['ZendX_Loader_StandardAutoloaderTest']);
    }

    public function testCanRegisterMultipleMapsAtOnce()
    {
        $map = array(
            'ZendX_Loader_Exception' => dirname(__FILE__) . '/../../../library/ZendX/Loader/Exception.php',
            'ZendX_Loader_StandardAutoloaderTest' => 'some/bogus/path.php',
        );
        $maps = array($map, dirname(__FILE__) . '/_files/goodmap.php');
        $this->loader->registerAutoloadMaps($maps);
        $test = $this->loader->getAutoloadMap();
        $this->assertTrue(is_array($test));
        $this->assertEquals(3, count($test));
    }

    public function testRegisterMapsThrowsExceptionForNonTraversableArguments()
    {
        $tests = array(true, 'string', 1, 1.0, new \stdClass);
        foreach ($tests as $test) {
            try {
                $this->loader->registerAutoloadMaps($test);
                $this->fail('Should not register non-traversable arguments');
            } catch (InvalidArgumentException $e) {
                $this->assertContains('array or implement Traversable', $e->getMessage());
            }
        }
    }

    public function testAutoloadLoadsClasses()
    {
        $map = array('ZendX_UnusualNamespace_ClassMappedClass' => dirname(__FILE__) . '/TestAsset/ClassMappedClass.php');
        $this->loader->registerAutoloadMap($map);
        $this->loader->autoload('ZendX_UnusualNamespace_ClassMappedClass');
        $this->assertTrue(class_exists('ZendX_UnusualNamespace_ClassMappedClass', false));
    }

    public function testIgnoresClassesNotInItsMap()
    {
        $map = array('ZendX_UnusualNamespace_ClassMappedClass' => dirname(__FILE__) . '/TestAsset/ClassMappedClass.php');
        $this->loader->registerAutoloadMap($map);
        $this->loader->autoload('ZendX_UnusualNamespace_UnMappedClass');
        $this->assertFalse(class_exists('ZendX_UnusualNamespace_UnMappedClass', false));
    }

    public function testRegisterRegistersCallbackWithSplAutoload()
    {
        $this->loader->register();
        $loaders = spl_autoload_functions();
        $this->assertTrue(count($this->loaders) < count($loaders));
        $test = array_pop($loaders);
        $this->assertEquals(array($this->loader, 'autoload'), $test);
    }
}
