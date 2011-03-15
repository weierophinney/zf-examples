<?php
namespace Zend\Di;

use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{
    public function testCanCreateObjectGraphFromArrayConfiguration()
    {
        $config  = $this->getConfig();
        $di      = new DependencyInjector();
        $builder = new Builder($di);
        $builder->fromArray($config);

        $inspected = $di->get('inspected');
        $injected  = $di->get('injected');
        $struct    = $di->get('struct');
        $params    = $di->get('params');

        $this->assertInstanceOf('Zend\Di\TestAsset\InspectedClass', $inspected);
        $this->assertInstanceOf('Zend\Di\TestAsset\InjectedMethod', $injected);
        $this->assertInstanceOf('Zend\Di\TestAsset\Struct', $struct);
        $this->assertInstanceOf('Zend\Di\TestAsset\DummyParams', $params);

        $this->assertEquals('FOO', $inspected->foo);
        $this->assertEquals('BAZ', $inspected->baz);

        $this->assertEquals(array('params' => array('param1' => 'foo', 'param2' => 'bar', 'foo' => 'bar')), (array) $params);

        $this->assertEquals(array('param1' => 'foo', 'param2' => 'bar'), (array) $struct);
        $this->assertSame($params, $injected->object, sprintf('Params: %s; Injected: %s', var_export($params, 1), var_export($injected, 1)));
    }

    public function testCanCreateObjectGraphFromZendConfig()
    {
        $this->markTestIncomplete();
    }

    public function getConfig()
    {
        return array(
            'definitions' => array(
                array(
                    'class' => 'Zend\Di\TestAsset\Struct',
                    'params' => array(
                        'param1' => 'foo',
                        'param2' => 'bar',
                    ),
                    'param_map' => array(
                        'param1' => 0,
                        'param2' => 1,
                    ),
                ),
                array(
                    'class' => 'Zend\Di\TestAsset\DummyParams',
                    'constructor_callback' => array(
                        'class'  => 'Zend\Di\TestAsset\StaticFactory',
                        'method' => 'factory',
                    ),
                    'params' => array(
                        'struct' => array('__reference' => 'struct'),
                        'params' => array('foo' => 'bar'),
                    ),
                    'param_map' => array(
                        'struct' => 0,
                        'params' => 1,
                    ),
                ),
                array(
                    'class' => 'Zend\Di\TestAsset\InjectedMethod',
                    'methods' => array(
                        array(
                            'name' => 'setObject',
                            'args' => array(
                                array('__reference' => 'params'),
                            ),
                        ),
                    ),
                ),
                array(
                    'class' => 'Zend\Di\TestAsset\InspectedClass',
                    'params' => array(
                        'baz' => 'BAZ',
                        'foo' => 'FOO',
                    ),
                ),
            ),
            'aliases' => array(
                'struct'    => 'Zend\Di\TestAsset\Struct',
                'params'    => 'Zend\Di\TestAsset\DummyParams',
                'injected'  => 'Zend\Di\TestAsset\InjectedMethod',
                'inspected' => 'Zend\Di\TestAsset\InspectedClass',
            ),
        );
        /*
        return array(
            'definitions' => array(
                array(
                    'class' => 'className',
                    'constructor_callback' => false,
                        // or string, or array; if array, 'class' and 'method' 
                        // strings
                    'params' => array(
                        'name' => 'value',
                        // if value is an array, look for '__reference' key, 
                        // and, if found, create a Reference object
                    ),
                    'param_map' => array(
                    ),
                    'tags' => array(),
                    'shared' => true,
                    'methods' => array(
                        array(
                            'name' => 'method_name',
                            'args' => array( /* ... * / ),
                                // if value is an array, look for '__reference' 
                                // key, and, if found, create a Reference object
                        ),
                    ),
                ),
            ),
            'aliases' => array(
                'alias' => 'target',
            ),
        );
         */
    }
}
