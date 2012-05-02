<?php
namespace Zend\Di;

use PHPUnit_Framework_TestCase as TestCase;

class DefinitionTest extends TestCase
{
    public function setUp()
    {
        $this->definition = new Definition(__CLASS__);
    }

    public function testCanRetrieveConfiguredClassName()
    {
        $this->assertEquals(__CLASS__, $this->definition->getClass());
    }

    public function testParamsAreEmptyByDefault()
    {
        foreach ($this->definition->getParams() as $param) {
            $this->assertNull($param);
        }
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

    public function testCanSpecifyManyParamsAtOnce()
    {
        $params = array(
            'foo' => 'FOO',
            'bar' => 'BAR',
        );
        $map = array('foo' => 0, 'bar' => 1);
        $this->definition->setParams($params)
                         ->setParamMap($map);
        $this->assertEquals(array_values($params), $this->definition->getParams());
    }

    public function testSettingParamMapWithNonNumericPositionsRaisesException()
    {
        $this->setExpectedException('Zend\Di\Exception\InvalidPositionException');
        $this->definition->setParamMap(array(
            'foo' => 0,
            'bar' => 'bar',
            'baz' => 2,
        ));
    }

    public function testSettingParamMapWithNonStringNameRaisesException()
    {
        $this->setExpectedException('Zend\Di\Exception\InvalidParamNameException');
        $this->definition->setParamMap(array(
            'foo' => 0,
            1     => 1,
            'baz' => 2,
        ));
    }

    public function testSettingParamMapWithInvalidPositionsRaisesException()
    {
        $this->setExpectedException('Zend\Di\Exception\InvalidPositionException', 'non-sequential');
        $this->definition->setParamMap(array(
            'foo' => 0,
            'bar' => 3,
            'baz' => 2,
        ));
    }

    public function testSharedByDefault()
    {
        $this->assertTrue($this->definition->isShared());
    }

    public function testCanOverrideSharedFlag()
    {
        $this->definition->setShared(false);
        $this->assertFalse($this->definition->isShared());
    }

    public function testAddingMethodCallsAggregates()
    {
        $this->definition->addMethodCall('foo', array());
        $this->definition->addMethodCall('bar', array('bar'));
        $methods = $this->definition->getMethodCalls();
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
        $this->definition->addTag('foo');
        $this->assertTrue($this->definition->hasTag('foo'));
    }

    /**
     * @dataProvider invalidTags
     */
    public function testPassingInvalidTagRaisesException($tag)
    {
        $this->setExpectedException('Zend\Di\Exception\InvalidArgumentException', 'Tag');
        $this->definition->addTag($tag);
    }

    public function invalidTags()
    {
        return array(
            array(1),
            array(1.0),
            array(false),
            array(new \stdClass),
            array(array()),
        );
    }

    public function testHasTagReturnsFalseWhenTagNotPresent()
    {
        $this->assertFalse($this->definition->hasTag('foo'));
    }

    public function testCanAddManyTagsAtOnce()
    {
        $tags = array(
            'foo',
            'bar',
            'baz',
        );
        $this->definition->addTags($tags);
        $this->assertEquals($tags, $this->definition->getTags());
    }

    public function testNoConstructorCallbackByDefault()
    {
        $this->assertFalse($this->definition->hasConstructorCallback());
    }

    public function testReturnsTrueForHasConstructorCallbackWhenOneProvided()
    {
        $callback = function () {};
        $this->definition->setConstructorCallback($callback);
        $this->assertTrue($this->definition->hasConstructorCallback());
    }

    public function testCanSetConstructorCallback()
    {
        $callback = function () {};
        $this->definition->setConstructorCallback($callback);
        $this->assertSame($callback, $this->definition->getConstructorCallback());
    }
}
