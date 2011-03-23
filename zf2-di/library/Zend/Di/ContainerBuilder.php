<?php
namespace Zend\Di;

use Zend\CodeGenerator\Php as CodeGen;

class ContainerBuilder
{
    protected $containerClass = 'ApplicationContext';

    protected $injector;

    public function __construct(DependencyInjection $injector)
    {
        $this->injector = $injector;
    }

    public function setContainerClass($name)
    {
        $this->containerClass = $name;
        return $this;
    }

    public function generateContainer($filename)
    {
        if (!is_writable($filename)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Unable to write to file "%s"; cannot generate container',
                $filename
            ));
        }

        $indent     = '    ';
        $aliases    = $this->reduceAliases($this->injector->getAliases());
        $statements = array();

        foreach ($this->injector->getDefinitions() as $definition) {
            $name  = $definition->getClass();

            // Build body of case statement
            //   - Create parameters
            //     - Get parameter list
            $params = $definition->getParams();
            
            //     - Foreach, in order:
            foreach ($params as $key => $param) {
            //       - If literal, use it
                if (is_scalar($param) || is_array($param)) {
                    // How do we do represent these?
                    $string = var_export($param, 1);
                    if (strstr($string, '::__set_state(')) {
                        throw new Exception\RuntimeException('Arguments in definitions may not contain objects');
                    }
                    $params[$key] = $string;
                } elseif ($param instanceof DependencyReference) {
            //       - If a reference, build "$this->get('{$name}')"
                    $params[$key] = sprintf('$this->get(\'%s\')', $param->getServiceName());
                } else {
                    // Don't think we can handle objects otherwise...
                    throw new Exception\RuntimeException('Unable to build containers from object arguments');
                }
            }

            //   - Create "new" statement
            $creation = '';
            if ($definition->hasConstructorCallback()) {
            //     - If using a callback, build it, using params as an array
                $callback = var_export($definition->getConstructorCallback(), 1);
                if (strstr($callback, '::__set_state(')) {
                    throw new Exception\RuntimeException('Unable to build containers that use callbacks requiring object instances');
                }
                if (count($params)) {
                    $creation = sprintf('$object = call_user_func(%s, %s);', $callback, implode(', ', $params));
                } else {
                    $creation = sprintf('$object = call_user_func(%s);', $callback);
                }
            } else {
            //     - If not, "new $class($paramlist)"
                $creation = sprintf('$object = new %s(%s);', $name, implode(', ', $params));
            }

            //   - Create method calls
            $methods = '';
            //     - Foreach method
            foreach ($definition->getMethodCalls() as $method) {
                $methodName   = $definition->getName();
                $methodParams = $definition->getArgs();
            //       - Create parameters
            //         - Foreach, in order:
                foreach ($methodParams as $key => $param) {
                //       - If literal, use it
                    if (is_scalar($param) || is_array($param)) {
                        // How do we do represent these?
                        $string = var_export($param, 1);
                        if (strstr($string, '::__set_state(')) {
                            throw new Exception\RuntimeException('Arguments in definitions may not contain objects');
                        }
                        $methodParams[$key] = $string;
                    } elseif ($param instanceof DependencyReference) {
                //       - If a reference, build "$this->get('{$name}')"
                        $methodParams[$key] = sprintf('$this->get(\'%s\')', $param->getServiceName());
                    } else {
                        // Don't think we can handle objects otherwise...
                        throw new Exception\RuntimeException('Unable to build containers from object arguments');
                    }
                }

            //       - Create call: $object->$method($params)
                $methods .= sprintf("%s\$object->%s(%s);\n", str_repeat($indent, 2), $methodName, implode(', ', $methodParams));
            }

            //   - Determine whether or not to store instance
            $storage = '';
            if ($definition->isShared()) {
            //     - If so, store it in services map
                $storage = sprintf("%s\$this->services['%s'] = \$object;\n", str_repeat($indent, 2), $name);
            }

            // Get cases for case statements
            $cases = array($name);
            if (isset($aliases[$name])) {
                $cases = array_merge($aliases[$name], $cases);
            }

            // Build case statement and store
            $statement = '';
            foreach ($cases as $value) {
                $statement .= sprintf("%scase '%s':\n", $indent, $value);
            }

            // Create fetch of stored service
            if ($definition->isShared()) {
                $statement .= sprintf("%sif (isset(\$this->services['%s'])) {\n", str_repeat($indent, 2), $name);
                $statement .= sprintf("%sreturn \$this->services['%s'];\n%s}\n\n", str_repeat($indent, 3), $name, str_repeat($indent, 2));
            }

            // Creation and method calls
            $statement .= sprintf("%s%s\n", str_repeat($indent, 2), $creation);
            $statement .= $methods;

            // Stored service
            $statement .= $storage;

            // End case
            $statement .= sprintf("%sreturn \$object;\n", str_repeat($indent, 2));

            $statements[] = $statement;
        }

        // Build switch statement
        $switch  = sprintf("switch (%s) {\n%s\n", '$name', implode("\n", $statements));
        $switch .= sprintf("%sdefault:\n%sreturn parent::get(%s, %s);\n", $indent, str_repeat($indent, 2), '$name', '$params');
        $switch .= "}\n\n";

        // Build get() method
        $nameParam   = new CodeGen\PhpParameter();
        $nameParam->setName('name');
        $paramsParam = new CodeGen\PhpParameter();
        $paramsParam->setName('params')
                    ->setDefaultValue(array())
                    ->setType('array');

        $get = new CodeGen\PhpMethod();
        $get->setName('get');
        $get->setParameters(array(
            $nameParam,
            $paramsParam,
        ));
        $get->setBody($switch);

        // Loop through aliases, and build getters
        //   - Normalize alias names
        //   - Proxy to get($alias)
        $aliasMethods = array();
        foreach ($aliases as $class => $classAliases) {
            foreach ($classAliases as $alias) {
                $aliasMethods[] = $this->getCodeGenMethodFromAlias($alias, $class);
            }
        }

        // Create class
        $container = new CodeGen\PhpClass();
        //   - class named after container class name
        $container->setName($this->containerClass);
        //   - class extends SL
        $container->setExtendedClass('ServiceLocator');
        //   - Attach get() method and alias methods to class
        $container->setMethod($get);
        $container->setMethods($aliasMethods);

        // Write file
        $classFile = new CodeGen\PhpFile();
        $classFile->setUse('Zend\Di\ServiceLocator')
                  ->setClass($container)
                  ->setFilename($filename);
        $classFile->write();
    }

    protected function reduceAliases(array $aliasList)
    {
        $reduced = array();
        $aliases = array_keys($aliasList);
        foreach ($aliasList as $alias => $service)
        {
            if (in_array($service, $aliases)) {
                do {
                    $service = $aliasList[$service];
                } while (in_array($service, $aliases));
            }
            if (!isset($reduced[$service])) {
                $reduced[$service] = array();
            }
            $reduced[$service][] = $alias;
        }
        return $reduced;
    }

    protected function getCodeGenMethodFromAlias($alias, $class)
    {
        $alias = $this->normalizeAlias($alias);
        $method = new CodeGen\PhpMethod();
        $method->setName($alias)
               ->setBody(sprintf('return $this->get(\'%s\');', $class));
        return $method;
    }

    protected function normalizeAlias($alias)
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]/', ' ', $alias);
        $normalized = 'get' . str_replace(' ', '', ucwords($normalized));
        return $normalized;
    }
}
