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
 * @package    ZendX_File
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Test class for ZendX_File_ClassFileLocator
 *
 * @category   ZendX
 * @package    ZendX_File
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      ZendX_File
 */
class ZendX_File_ClassFileLocatorTest extends PHPUnit_Framework_TestCase
{

    public function testConstructorThrowsInvalidArgumentExceptionForInvalidStringDirectory()
    {
        $this->setExpectedException('InvalidArgumentException');
        $locator = new ZendX_File_ClassFileLocator('__foo__');
    }

    public function testConstructorThrowsInvalidArgumentExceptionForNonDirectoryIteratorArgument()
    {
        $iterator = new ArrayIterator(array());
        $this->setExpectedException('InvalidArgumentException');
        $locator = new ZendX_File_ClassFileLocator($iterator);
    }

    public function testIterationShouldReturnOnlyPhpFiles()
    {
        $locator = new ZendX_File_ClassFileLocator(dirname(__FILE__));
        foreach ($locator as $file) {
            $this->assertRegexp('/\.php$/', $file->getFilename());
        }
    }

    public function testIterationShouldReturnOnlyPhpFilesContainingClasses()
    {
        $locator = new ZendX_File_ClassFileLocator(dirname(__FILE__));
        $found = false;
        foreach ($locator as $file) {
            if (preg_match('/locator-should-skip-this\.php$/', $file->getFilename())) {
                $found = true;
            }
        }
        $this->assertFalse($found, "Found PHP file not containing a class?");
    }

    public function testIterationShouldReturnInterfaces()
    {
        $locator = new ZendX_File_ClassFileLocator(dirname(__FILE__));
        $found = false;
        foreach ($locator as $file) {
            if (preg_match('/LocatorShouldFindThis\.php$/', $file->getFilename())) {
                $found = true;
            }
        }
        $this->assertTrue($found, "Locator skipped an interface?");
    }

    /**
     * Disabling, as focussing on PHP 5.2
     *
     * @group disable
     */
    public function testIterationShouldInjectNamespaceInFoundItems()
    {
        $locator = new ZendX_File_ClassFileLocator(dirname(__FILE__));
        $found = false;
        foreach ($locator as $file) {
            $this->assertTrue(isset($file->namespace));
        }
    }

    public function testIterationShouldInjectClassInFoundItems()
    {
        $locator = new ZendX_File_ClassFileLocator(dirname(__FILE__));
        $found = false;
        foreach ($locator as $file) {
            $this->assertTrue(isset($file->classname));
        }
    }
}
