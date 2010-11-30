<?php

namespace Zf\Util;

class DependenciesTest extends \PHPUnit_Framework_TestCase
{
    public function testRaisesExceptionIfCannotFindFile()
    {
        $this->setExpectedException('InvalidArgumentException');
        Dependencies::getForFile('/I/do/not/exist.php');
    }

    public function testCanParseDependenciesForFilesOnIncludePath()
    {
        set_include_path(implode(PATH_SEPARATOR, array(
            '.',
            __DIR__ . '/_files',
            get_include_path(),
        )));
        $deps = Dependencies::getForFile('TestCase1.php');
        $this->assertEquals(array('Foo\Bar'), $deps);
    }

    public function testDependenciesAreReturnedAsIsWhenNoNamespaceDeclared()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase2.php');
        $expected = array(
            'Foo\Bar',
            'Bar\Baz',
            'Baz\Bat',
        );
        $this->assertEquals($expected, $deps);
    }

    public function testDependencyListOmitsThoseInSameComponentNamespace()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase3.php');
        $expected = array(
            'Foo\Bar',
            'Baz\Bat',
        );
        $this->assertEquals(count($expected), count($deps));
        foreach ($expected as $class) {
            $this->assertContains($class, $deps);
        }
    }

    public function testReturnsEmptyArrayIfNoDependenciesFound()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase4.php');
        $this->assertEquals(array(), $deps);
    }

    public function testImportAliasesAreIgnored()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase5.php');
        $expected = array(
            'Foo\Bar',
            'Baz\Bat',
        );
        $this->assertEquals(count($expected), count($deps));
        foreach ($expected as $class) {
            $this->assertContains($class, $deps);
        }
    }

    public function testReturnsUniquesOnly()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase6.php');
        $expected = array(
            'Foo\Bar',
            'Baz\Bat',
        );
        $this->assertEquals(count($expected), count($deps));
        foreach ($expected as $class) {
            $this->assertContains($class, $deps);
        }
    }

    public function testIgnoresUseStatementsFromClosures()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase7.php');
        $this->assertNotContains('ENT_COMPAT', $deps);
    }

    public function testIgnoresTopLevelNamespaceIfMatchesCurrentVendor()
    {
        $deps = Dependencies::getForFile(__DIR__ . '/_files/TestCase8.php');
        $this->assertNotContains('Zend', $deps);
    }
}
