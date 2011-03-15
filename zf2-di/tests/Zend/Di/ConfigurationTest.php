<?php
namespace Zend\Di;

use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{
    public function testCanCreateObjectGraphFromArrayConfiguration()
    {
        $this->markTestIncomplete();
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
                            'args' => array( /* ... */ ),
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
    }
}
