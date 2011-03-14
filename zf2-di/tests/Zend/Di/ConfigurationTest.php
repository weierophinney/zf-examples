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

    /**
     * @todo notation for references...
     */
    public function getConfig()
    {
        return array(
            'definitions' => array(
                array(
                    'class' => 'className'
                    'constructor_callback' => false,
                    'params' => array(
                        'name' => 'value',
                    ),
                    'param_map' => array(
                    ),
                    'tags' => array()
                    'shared' => true,
                    'methods' => array(
                        array(
                            'name' => 'method_name',
                            'args' => array( ... ),
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
