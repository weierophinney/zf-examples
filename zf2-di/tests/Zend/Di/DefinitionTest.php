<?php
namespace Zend\Di;

use PHPUnit_Framework_TestCase as TestCase;

class DefinitionTest extends TestCase
{
    public function testCanRetrieveConfiguredClassName()
    {
        $def = new Definition(__CLASS__);
        $this->assertEquals(__CLASS__, $def->getClass());
    }

    public function testParamsAreEmptyByDefault()
    {
        $def = new Definition(__CLASS__);
        $this->assertEquals(array(), $def->getParams());
    }

    public function testParamsAreReturnedInConstructorOrderWhenNoParamMapProvided()
    {
        $def = new Definition('Zend\Di\TestAsset\InspectedClass');
        $def->setParam('baz', 'BAZ');
        $def->setParam('foo', 'FOO');
        $expected = array(
            'FOO',
            'BAZ',
        );
        $this->assertEquals($expected, $def->getParams());
    }

    public function testParamsAreReturnedInParamMapOrderIfSpecified()
    {
        $def = new Definition('Zend\Di\TestAsset\InspectedClass');
        $def->setParam('baz', 'BAZ');
        $def->setParam('foo', 'FOO');
        $def->setParamMap(array(
            'baz' => 0,
            'foo' => 1,
        ));
        $expected = array(
            'BAZ',
            'FOO',
        );
        $this->assertEquals($expected, $def->getParams());
    }

    public function testSpecifyingAParamMultipleTimesOverwrites()
    {
        $def = new Definition('Zend\Di\TestAsset\InspectedClass');
        $def->setParam('baz', 'BAZ');
        $def->setParam('foo', 'FOO');
        $def->setParam('baz', 'baz');
        $def->setParam('foo', 'foo');
        $def->setParamMap(array(
            'baz' => 0,
            'foo' => 1,
        ));
        $expected = array(
            'baz',
            'foo',
        );
        $this->assertEquals($expected, $def->getParams());
    }

    public function testSharedByDefault()
    {
        $def = new Definition(__CLASS__);
        $this->assertTrue($def->isShared());
    }

    public function testCanOverrideSharedFlag()
    {
        $def = new Definition(__CLASS__);
        $def->setShared(false);
        $this->assertFalse($def->isShared());
    }

    public function testAddingMethodCallsAggregates()
    {
        $def = new Definition(__CLASS__);
        $def->addMethodCall('foo', array());
        $def->addMethodCall('bar', array('bar'));
        $methods = $def->getMethodCalls();
        $this->assertInstanceOf('Zend\Di\InjectibleMethods', $methods);
        foreach ($methods as $name => $method) {
            switch ($name) {
                case 'foo':
                    $this->assertSame(array(), $method->getArgs());
                    break;
                case 'bar':
                    $this->assertSame(array('bar'), $method->getArgs());
                    break;
                default:
                    $this->fail('Unexpected method encountered');
            }
        }
    }

    public function testCanAddSingleTags()
    {
        $def = new Definition(__CLASS__);
        $def->addTag('foo');
        $this->assertTrue($def->hasTag('foo'));
    }

    public function testHasTagReturnsFalseWhenTagNotPresent()
    {
        $def = new Definition(__CLASS__);
        $this->assertFalse($def->hasTag('foo'));
    }

    public function testCanAddManyTagsAtOnce()
    {
        $tags = array(
            'foo',
            'bar',
            'baz',
        );
        $def = new Definition(__CLASS__);
        $def->addTags($tags);
        $this->assertEquals($tags, $def->getTags());
    }
}
