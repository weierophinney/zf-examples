<?php
namespace Zend\Di;

interface DependencyInjection
{
    public function get($serviceName, array $params = null);
    public function newInstance($className, array $params = null);
    
    /**
     * @param  array|Traversable $definitions Iterable Definition objects
     */
    public function setDefinitions($definitions);
    
    public function setDefinition($serviceName, DependencyDefinition Definition);
    public function setAlias($alias, $serviceName);
}
