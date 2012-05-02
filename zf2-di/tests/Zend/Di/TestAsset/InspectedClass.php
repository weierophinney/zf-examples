<?php
namespace Zend\Di\TestAsset;

class InspectedClass
{
    public function __construct($foo, $baz)
    {
        $this->foo = $foo;
        $this->baz = $baz;
    }
}
