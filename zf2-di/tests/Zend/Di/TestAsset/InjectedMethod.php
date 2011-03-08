<?php
namespace Zend\Di\TestAsset;

class InjectedMethod
{
    public function setObject($o)
    {
        $this->object = $o;
    }
}
