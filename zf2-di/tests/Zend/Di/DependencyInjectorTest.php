<?php
namespace Zend\Di;

use PHPUnit_Framework_TestCase as TestCase;

class DependencyInjectorTest extends TestCase
{
    public function setUp()
    {
        $this->di = new DependencyInjector;
    }

    public function testPassingInvalidDefinitionRaisesException()
    {
        $definitions = array('foo');
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->di->setDefinitions($definitions);
    }

    public function testGetRetrievesObjectWithMatchingClassDefinition()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def);
        $test = $this->di->get('Zend\Di\TestAsset\Struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test);
        $this->assertEquals('foo', $test->param1);
        $this->assertEquals('bar', $test->param2);
    }

    public function testGetRetrievesSameInstanceOnSubsequentCalls()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def);
        $first  = $this->di->get('Zend\Di\TestAsset\Struct');
        $second = $this->di->get('Zend\Di\TestAsset\Struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $first);
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $second);
        $this->assertSame($first, $second);
    }

    public function testGetCanRetrieveByProvidedServiceName()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def, 'struct');
        $test = $this->di->get('struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test);
        $this->assertEquals('foo', $test->param1);
        $this->assertEquals('bar', $test->param2);
    }

    public function testGetCanRetrieveByClassNameWhenServiceNameIsAlsoProvided()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def, 'struct');
        $test = $this->di->get('Zend\Di\TestAsset\Struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test);
        $this->assertEquals('foo', $test->param1);
        $this->assertEquals('bar', $test->param2);
    }

    public function testGetReturnsNewInstanceIfDefinitionSharedFlagIsFalse()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar')
            ->setShared(false);
        $this->di->setDefinition($def);
        $first  = $this->di->get('Zend\Di\TestAsset\Struct');
        $second = $this->di->get('Zend\Di\TestAsset\Struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $first);
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $second);
        $this->assertNotSame($first, $second);
    }

    public function testNewInstanceForcesNewObjectInstanceEvenWhenSharedFlagIsTrue()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar')
            ->setShared(true);
        $this->di->setDefinition($def);
        $first  = $this->di->get('Zend\Di\TestAsset\Struct');
        $second = $this->di->newInstance('Zend\Di\TestAsset\Struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $first);
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $second);
        $this->assertNotSame($first, $second);
    }

    public function testGetNewInstanceByServiceName()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def, 'struct');
        $test = $this->di->newInstance('struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test);
    }

    public function testGetNewInstanceByAlias()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def);
        $this->di->setAlias('struct', 'Zend\Di\TestAsset\Struct');
        
        $test = $this->di->newInstance('struct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test);
    }

    public function testCanAliasToServiceName()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def, 'struct');
        $this->di->setAlias('mystruct', 'struct');
        
        $test = $this->di->newInstance('mystruct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test);
    }

    public function testCanApplyMultipleAliasesPerDefinition()
    {
        $def = new Definition('Zend\Di\TestAsset\Struct');
        $def->setParam('param1', 'foo')
            ->setParam('param2', 'bar');
        $this->di->setDefinition($def);
        $this->di->setAlias('mystruct', 'Zend\Di\TestAsset\Struct');
        $this->di->setAlias('struct', 'Zend\Di\TestAsset\Struct');
        
        $test1 = $this->di->newInstance('struct');
        $test2 = $this->di->newInstance('mystruct');
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test1);
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test2);
        $this->assertSame($test1, $test2);
    }

    public function testGetReturnsNullIfNoMatchingClassOrDefinitionFound()
    {
        $classes = get_declared_classes();
        $class   = array_pop($classes) . uniqid();
        while (in_array($class, $classes)) {
            $class .= uniqid();
        }

        $this->assertNull($this->di->get($class));
    }

    public function testNewInstanceReturnsNullIfNoMatchingClassOrDefinitionFound()
    {
        $classes = get_declared_classes();
        $class   = array_pop($classes) . uniqid();
        while (in_array($class, $classes)) {
            $class .= uniqid();
        }

        $this->assertNull($this->di->newInstance($class));
    }

    public function testUnmatchedReferenceInDefinitionParametersResultsInNullInjection()
    {
        $struct   = new Definition('Zend\Di\TestAsset\Struct');
        $struct->setParam('param1', 'foo')
               ->setParam('param2', new Reference('voodoo'));
        $this->di->setDefinition($struct);
        $test = $this->di->get('Zend\Di\TestAsset\Struct');
        $this->assertNull($test->param2);
    }

    public function testReferenceInDefinitionParametersCausesInjection()
    {
        $composed = new Definition('Zend\Di\TestAsset\ComposedClass');
        $struct   = new Definition('Zend\Di\TestAsset\Struct');
        $struct->setParam('param1', 'foo')
               ->setParam('param2', new Reference('Zend\Di\TestAsset\ComposedClass'));
        $this->di->setDefinition($composed)
                 ->setDefinition($struct);

        $diStruct  = $this->di->get('Zend\Di\TestAsset\Struct');
        $diCompose = $this->di->get('Zend\Di\TestAsset\ComposedClass');
        $this->assertSame($diCompose, $diStruct->param2);
    }

    public function testReferenceToServiceNameInDefinitionParametersCausesInjection()
    {
        $composed = new Definition('Zend\Di\TestAsset\ComposedClass');
        $struct   = new Definition('Zend\Di\TestAsset\Struct');
        $struct->setParam('param1', 'foo')
               ->setParam('param2', new Reference('composed'));
        $this->di->setDefinition($composed, 'composed')
                 ->setDefinition($struct);

        $diStruct  = $this->di->get('Zend\Di\TestAsset\Struct');
        $diCompose = $this->di->get('Zend\Di\TestAsset\ComposedClass');
        $this->assertSame($diCompose, $diStruct->param2);
    }

    public function testCanInjectNestedItems()
    {
        $inspect  = new Definition('Zend\Di\TestAsset\InspectedClass');
        $inspect->setParam('foo', new Reference('composed'))
                ->setParam('baz', 'BAZ');
        $composed = new Definition('Zend\Di\TestAsset\ComposedClass');
        $struct   = new Definition('Zend\Di\TestAsset\Struct');
        $struct->setParam('param1', 'foo')
               ->setParam('param2', new Reference('inspect'));
        $this->di->setDefinition($composed, 'composed')
                 ->setDefinition($inspect, 'inspect')
                 ->setDefinition($struct, 'struct');

        $diStruct  = $this->di->get('struct');
        $diInspect = $this->di->get('inspect');
        $diCompose = $this->di->get('composed');
        $this->assertSame($diCompose, $diInspect->foo);
        $this->assertSame($diInspect, $diStruct->param2);
    }

    public function testLastDefinitionOfSameClassNameWins()
    {
        $struct1 = new Definition('Zend\Di\TestAsset\Struct');
        $struct1->setParam('param1', 'foo')
                ->setParam('param2', 'bar');
        $struct2 = new Definition('Zend\Di\TestAsset\Struct');
        $struct2->setParam('param1', 'FOO')
                ->setParam('param2', 'BAR');
        $this->di->setDefinition($struct1)
                 ->setDefinition($struct2);
        $test = $this->di->get('Zend\Di\TestAsset\Struct');
        $this->assertEquals('FOO', $test->param1);
        $this->assertEquals('BAR', $test->param2);
    }

    public function testLastDefinitionOfSameClassNameWinsEvenWhenAddedWithDifferentServiceNames()
    {
        $struct1 = new Definition('Zend\Di\TestAsset\Struct');
        $struct1->setParam('param1', 'foo')
                ->setParam('param2', 'bar');
        $struct2 = new Definition('Zend\Di\TestAsset\Struct');
        $struct2->setParam('param1', 'FOO')
                ->setParam('param2', 'BAR');
        $this->di->setDefinition($struct1, 'struct1')
                 ->setDefinition($struct2, 'struct2');
        $test = $this->di->get('struct1');
        $this->assertEquals('FOO', $test->param1);
        $this->assertEquals('BAR', $test->param2);
    }

    public function testCanInjectSpecificMethods()
    {
        $struct = new Definition('Zend\Di\TestAsset\Struct');
        $struct->setParam('param1', 'foo')
               ->setParam('param2', 'bar');
        $def = new Definition('Zend\Di\TestAsset\InjectedMethod');
        $def->addMethodCall('setObject', array(new Reference('struct')));
        $this->di->setDefinition($def)
                 ->setDefinition($struct, 'struct');

        $test = $this->di->get('Zend\Di\TestAsset\InjectedMethod');
        $this->assertInstanceOf('Zend\Di\TestAsset\InjectedMethod', $test);
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $test->object);
        $this->assertSame($test->object, $this->di->get('struct'));
    }

    /**
     * @todo tests for recursive DI calls
     */
}
