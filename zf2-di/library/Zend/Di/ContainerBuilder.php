<?php
namespace Zend\Di;

use Zend\CodeGenerator\Php as CodeGen;

class ContainerBuilder
{
    protected $containerClass = 'ApplicationContext';

    protected $injector;

    protected $namespace;

    /**
     * Constructor
     *
     * Requires a DependencyInjection manager on which to operate.
     * 
     * @param  DependencyInjection $injector 
     * @return void
     */
    public function __construct(DependencyInjection $injector)
    {
        $this->injector = $injector;
    }

    /**
     * Set the class name for the generated service locator container
     * 
     * @param  string $name 
     * @return ContainerBuilder
     */
    public function setContainerClass($name)
    {
        $this->containerClass = $name;
        return $this;
    }

    /**
     * Set the namespace to use for the generated class file
     * 
     * @param  string $namespace 
     * @return ContainerBuilder
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Construct, configure, and return a PHP classfile code generation object
     *
     * Creates a Zend\CodeGenerator\Php\PhpFile object that has 
     * created the specified class and service locator methods.
     * 
     * @param  null|string $filename 
     * @return CodeGen\PhpFile
     */
    public function getCodeGenerator($filename = null)
    {
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
                if (null === $param) {
                    $params[$key] = null;
                } elseif (is_scalar($param) || is_array($param)) {
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
                    $message = sprintf('Unable to use object arguments when building containers. Encountered with "%s", parameter of type "%s"', $name, get_class($param));
                    throw new Exception\RuntimeException($message);
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
                $methodName   = $method->getName();
                $methodParams = $method->getArgs();
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
        $defaultParams = new CodeGen\PhpParameterDefaultValue();
        $defaultParams->setValue(array());
        $paramsParam = new CodeGen\PhpParameter();
        $paramsParam->setName('params')
                    ->setType('array')
                    ->setDefaultValue($defaultParams);

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

        // Create PHP file code generation object
        $classFile = new CodeGen\PhpFile();
        $classFile->setUse('Zend\Di\ServiceLocator')
                  ->setClass($container);

        if (null !== $this->namespace) {
            $classFile->setNamespace($this->namespace);
        }

        if (null !== $filename) {
            $classFile->setFilename($filename);
        }

        return $classFile;
    }

    /**
     * Reduces aliases
     *
     * Takes alias list and reduces it to a 2-dimensional array of 
     * class names pointing to an array of aliases that resolve to 
     * it.
     * 
     * @param  array $aliasList 
     * @return array
     */
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

    /**
     * Create a PhpMethod code generation object named after a given alias
     * 
     * @param  string $alias 
     * @param  class $class Class to which alias refers
     * @return CodeGen\PhpMethod
     */
    protected function getCodeGenMethodFromAlias($alias, $class)
    {
        $alias = $this->normalizeAlias($alias);
        $method = new CodeGen\PhpMethod();
        $method->setName($alias)
               ->setBody(sprintf('return $this->get(\'%s\');', $class));
        return $method;
    }

    /**
     * Normalize an alias to a getter method name
     * 
     * @param  string $alias 
     * @return string
     */
    protected function normalizeAlias($alias)
    {
        $normalized = preg_replace('/[^a-zA-Z0-9]/', ' ', $alias);
        $normalized = 'get' . str_replace(' ', '', ucwords($normalized));
        return $normalized;
    }
}
