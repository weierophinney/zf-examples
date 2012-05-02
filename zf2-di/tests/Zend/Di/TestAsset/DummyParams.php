<?php
namespace Zend\Di\TestAsset;

class DummyParams
{
    public $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }
}
