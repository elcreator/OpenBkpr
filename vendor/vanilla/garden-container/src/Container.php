<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container;

use phpDocumentor\Reflection\Types\Boolean;
use Psr\Container\ContainerInterface;

/**
 * An inversion of control container.
 */
class Container implements ContainerInterface, ContainerConfigurationInterface
{
    private $currentRule;
    private $currentRuleName;
    private $instances;
    private $rules;
    private $factories;

    /**
     * Construct a new instance of the {@link Container} class.
     */
    public function __construct()
    {
        $this->rules = ["*" => ["inherit" => true, "constructorArgs" => null]];
        $this->instances = [];
        $this->factories = [];

        $this->rule("*");
    }

    /**
     * Deep clone rules.
     */
    public function __clone()
    {
        $this->rules = $this->arrayClone($this->rules);
        $this->rule($this->currentRuleName);
    }

    /**
     * Clear all instances
     *
     */
    public function clearInstances()
    {
        $this->instances = [];
    }

    ///
    /// ContainerConfiguration implementation.
    ///

    /**
     * @inheritDoc
     */
    public function defaultRule()
    {
        return $this->rule("*");
    }

    /**
     * @inheritDoc
     */
    public function rule($id)
    {
        $id = $this->normalizeID($id);

        if (!isset($this->rules[$id])) {
            $this->rules[$id] = [];
        }
        $this->currentRuleName = $id;
        $this->currentRule = &$this->rules[$id];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasRule(string $id): bool
    {
        $id = $this->normalizeID($id);
        return !empty($this->rules[$id]);
    }

    /**
     * @inheritDoc
     */
    public function getClass(): string
    {
        return empty($this->currentRule["class"]) ? "" : $this->currentRule["class"];
    }

    /**
     * @inheritDoc
     */
    public function setClass(string $className)
    {
        $this->currentRule["class"] = $className;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAliasOf(): string
    {
        return empty($this->currentRule["aliasOf"]) ? "" : $this->currentRule["aliasOf"];
    }

    /**
     * @inheritDoc
     */
    public function setAliasOf(string $alias)
    {
        $alias = $this->normalizeID($alias);

        if ($alias === $this->currentRuleName) {
            trigger_error("You cannot set alias '$alias' to itself.", E_USER_NOTICE);
        } else {
            $this->currentRule["aliasOf"] = $alias;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAlias(string ...$alias)
    {
        foreach ($alias as $name) {
            $name = $this->normalizeID($name);

            if ($name === $this->currentRuleName) {
                trigger_error("Tried to set alias '$name' to self.", E_USER_NOTICE);
            } else {
                $this->rules[$name]["aliasOf"] = $this->currentRuleName;
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeAlias(string $alias)
    {
        $alias = $this->normalizeID($alias);

        if (!empty($this->rules[$alias]["aliasOf"]) && $this->rules[$alias]["aliasOf"] !== $this->currentRuleName) {
            trigger_error("Alias '$alias' does not point to the current rule.", E_USER_NOTICE);
        }

        unset($this->rules[$alias]["aliasOf"]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        $result = [];

        foreach ($this->rules as $name => $rule) {
            if (!empty($rule["aliasOf"]) && $rule["aliasOf"] === $this->currentRuleName) {
                $result[] = $name;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getFactory(): ?callable
    {
        return isset($this->currentRule["factory"]) ? $this->currentRule["factory"] : null;
    }

    /**
     * @inheritDoc
     */
    public function setFactory(?callable $factory = null)
    {
        $this->currentRule["factory"] = $factory;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isShared(): bool
    {
        return !empty($this->currentRule["shared"]);
    }

    /**
     * @inheritDoc
     */
    public function setShared(bool $shared)
    {
        $this->currentRule["shared"] = $shared;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInherit(): bool
    {
        return !empty($this->currentRule["inherit"]);
    }

    /**
     * @inheritDoc
     */
    public function setInherit(bool $inherit)
    {
        $this->currentRule["inherit"] = $inherit;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConstructorArgs(): array
    {
        return empty($this->currentRule["constructorArgs"]) ? [] : $this->currentRule["constructorArgs"];
    }

    /**
     * @inheritDoc
     */
    public function setConstructorArgs(array $args)
    {
        $this->currentRule["constructorArgs"] = $args;
        return $this;
    }

    /**
     * Set a specific shared instance into the container.
     *
     * When you set an instance into the container then it will always be returned by subsequent retrievals, even if a
     * rule is configured that says that instances should not be shared.
     *
     * @param string $name The name of the container entry.
     * @param mixed $instance This instance.
     * @return $this
     */
    public function setInstance(string $name, $instance)
    {
        $this->instances[$this->normalizeID($name)] = $instance;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addCall(string $method, array $args = [])
    {
        $this->currentRule["calls"][] = [$method, $args];

        // Something added a rule. If we have any existing factories make sure we clear them.
        if (isset($this->factories[$this->currentRuleName])) {
            unset($this->factories[$this->currentRuleName]);
        }

        return $this;
    }

    ///
    /// Container implementation.
    ///

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @template T
     * @param class-string<T>|string $id Identifier of the entry to look for.
     * @param array $args Additional arguments to pass to the constructor.
     *
     * @throws NotFoundException No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return T|mixed Entry.
     */
    public function getArgs($id, array $args = [])
    {
        $id = $this->normalizeID($id);

        if (isset($this->instances[$id])) {
            // A shared instance just gets returned.
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            // The factory for this object type is already there so call it to create the instance.
            return $this->factories[$id]($args);
        }

        if (!empty($this->rules[$id]["aliasOf"])) {
            // This rule references another rule.
            return $this->getArgs($this->rules[$id]["aliasOf"], $args);
        }

        // The factory or instance isn't registered so do that now.
        // This call also caches the instance or factory fo faster access next time.
        return $this->createInstance($id, $args);
    }

    /**
     * Call a callback with argument injection.
     *
     * @param callable $callback The callback to call.
     * @param array $args Additional arguments to pass to the callback.
     * @return mixed Returns the result of the callback.
     * @throws ContainerException Throws an exception if the callback cannot be understood.
     */
    public function call(callable $callback, array $args = [])
    {
        $instance = null;

        if (is_array($callback)) {
            $function = new \ReflectionMethod($callback[0], $callback[1]);

            if (is_object($callback[0])) {
                $instance = $callback[0];
            }
        } elseif (is_string($callback) || $callback instanceof \Closure) {
            $function = new \ReflectionFunction($callback);
        } else {
            // Assume we are an invokable.
            $function = new \ReflectionMethod($callback, "__invoke");
            $callback = [$callback, "__invoke"];
        }
        $args = $this->resolveArgs($this->makeDefaultArgs($function, $args), [], $instance);

        return call_user_func_array($callback, $args);
    }

    /**
     * Returns true if the container can return an entry for the given identifier. Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean`
     */
    public function has(string $id): bool
    {
        $id = $this->normalizeID($id);

        return isset($this->instances[$id]) || !empty($this->rules[$id]) || class_exists($id);
    }

    /**
     * Returns true if the container already has an instance for the given identifier. Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function hasInstance($id)
    {
        $id = $this->normalizeID($id);

        return isset($this->instances[$id]);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @template T
     * @param class-string<T>|string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return T|mixed Entry.
     */
    public function get($id)
    {
        return $this->getArgs($id);
    }

    ///
    /// Private utilities.
    ///

    /**
     * Deep clone an array.
     *
     * @param array $array The array to clone.
     * @return array Returns the cloned array.
     * @see http://stackoverflow.com/a/17729234
     */
    private function arrayClone(array $array)
    {
        return array_map(function ($element) {
            return is_array($element) ? $this->arrayClone($element) : (is_object($element) ? clone $element : $element);
        }, $array);
    }

    /**
     * Normalize a container entry ID.
     *
     * @param string $id The ID to normalize.
     * @return string Returns a normalized ID as a string.
     */
    private function normalizeID($id)
    {
        return ltrim($id, "\\");
    }

    /**
     * Make a rule based on an ID.
     *
     * @param string $nid A normalized ID.
     * @return array Returns an array representing a rule.
     */
    private function makeRule($nid)
    {
        $rule = isset($this->rules[$nid]) ? $this->rules[$nid] : [];

        if (class_exists($nid)) {
            for ($class = get_parent_class($nid); !empty($class); $class = get_parent_class($class)) {
                // Don't add the rule if it doesn't say to inherit.
                if (
                    !isset($this->rules[$class]) ||
                    (isset($this->rules[$class]["inherit"]) && !$this->rules[$class]["inherit"])
                ) {
                    continue;
                }
                $rule += $this->rules[$class];
            }

            // Add the default rule.
            if (!empty($this->rules["*"]["inherit"])) {
                $rule += $this->rules["*"];
            }

            // Add interface calls to the rule.
            $interfaces = class_implements($nid);
            foreach ($interfaces as $interface) {
                if (isset($this->rules[$interface])) {
                    $interfaceRule = $this->rules[$interface];

                    if (isset($interfaceRule["inherit"]) && $interfaceRule["inherit"] === false) {
                        continue;
                    }

                    if (!isset($rule["shared"]) && isset($interfaceRule["shared"])) {
                        $rule["shared"] = $interfaceRule["shared"];
                    }

                    if (!isset($rule["constructorArgs"]) && isset($interfaceRule["constructorArgs"])) {
                        $rule["constructorArgs"] = $interfaceRule["constructorArgs"];
                    }

                    if (!empty($interfaceRule["calls"])) {
                        $rule["calls"] = array_merge(
                            isset($rule["calls"]) ? $rule["calls"] : [],
                            $interfaceRule["calls"],
                        );
                    }
                }
            }
        } elseif (!empty($this->rules["*"]["inherit"])) {
            // Add the default rule.
            $rule += $this->rules["*"];
        }

        return $rule;
    }

    /**
     * Make a function that creates objects from a rule.
     *
     * @param string $nid The normalized ID of the container item.
     * @param array $rule The resolved rule for the ID.
     * @return callable Returns a function that when called will create a new instance of the class.
     * @throws NotFoundException No entry was found for this identifier.
     */
    private function makeFactory($nid, array $rule)
    {
        $className = empty($rule["class"]) ? $nid : $rule["class"];

        if (!empty($rule["factory"])) {
            // The instance is created with a user-supplied factory function.
            $callback = $rule["factory"];
            $function = $this->reflectCallback($callback);

            if ($function->getNumberOfParameters() > 0) {
                $callbackArgs = $this->makeDefaultArgs($function, (array) $rule["constructorArgs"]);
                $factory = function ($args) use ($callback, $callbackArgs) {
                    return call_user_func_array($callback, $this->resolveArgs($callbackArgs, $args));
                };
            } else {
                $factory = $callback;
            }

            // If a class is specified then still reflect on it so that calls can be made against it.
            if (class_exists($className)) {
                $class = new \ReflectionClass($className);
            }
        } else {
            // The instance is created by newing up a class.
            if (!class_exists($className)) {
                throw new NotFoundException("Class $className does not exist.", 404);
            }
            $class = new \ReflectionClass($className);
            $constructor = $class->getConstructor();

            if ($constructor && $constructor->getNumberOfParameters() > 0) {
                $constructorArgs = $this->makeDefaultArgs($constructor, (array) $rule["constructorArgs"]);

                $factory = function ($args) use ($className, $constructorArgs) {
                    return new $className(...array_values($this->resolveArgs($constructorArgs, $args)));
                };
            } else {
                $factory = function () use ($className) {
                    return new $className();
                };
            }
        }

        // Add calls to the factory.
        if (isset($class) && !empty($rule["calls"])) {
            $calls = [];

            // Generate the calls array.
            foreach ($rule["calls"] as $call) {
                [$methodName, $args] = $call;
                $method = $class->getMethod($methodName);
                $calls[] = [$methodName, $this->makeDefaultArgs($method, $args)];
            }

            // Wrap the factory in one that makes the calls.
            $factory = function ($args) use ($factory, $calls) {
                /**
                 * @psalm-suppress TooManyArguments
                 */
                $instance = $factory($args);

                foreach ($calls as $call) {
                    [$methodName, $defaultArgs] = $call;
                    $finalArgs = $this->resolveArgs($defaultArgs, [], $instance);
                    call_user_func_array([$instance, $methodName], $finalArgs);
                }

                return $instance;
            };
        }

        return $factory;
    }

    /**
     * Create a shared instance of a class from a rule.
     *
     * This method has the side effect of adding the new instance to the internal instances array of this object.
     *
     * @param string $nid The normalized ID of the container item.
     * @param array $rule The resolved rule for the ID.
     * @param array $args Additional arguments passed during creation.
     * @return object Returns the the new instance.
     * @throws NotFoundException Throws an exception if the class does not exist.
     */
    private function createSharedInstance($nid, array $rule, array $args)
    {
        if (!empty($rule["factory"])) {
            // The instance is created with a user-supplied factory function.
            $callback = $rule["factory"];
            $function = $this->reflectCallback($callback);

            if ($function->getNumberOfParameters() > 0) {
                $callbackArgs = $this->resolveArgs(
                    $this->makeDefaultArgs($function, (array) $rule["constructorArgs"]),
                    $args,
                );

                $this->instances[$nid] = null; // prevent cyclic dependency from infinite loop.
                $this->instances[$nid] = $instance = call_user_func_array($callback, $callbackArgs);
            } else {
                $this->instances[$nid] = $instance = $callback();
            }

            // Reflect on the instance so that calls can be made against it.
            if (is_object($instance)) {
                $class = new \ReflectionClass(get_class($instance));
            }
        } else {
            $className = empty($rule["class"]) ? $nid : $rule["class"];
            if (!class_exists($className)) {
                throw new NotFoundException("Class $className does not exist.", 404);
            }
            $class = new \ReflectionClass($className);
            $constructor = $class->getConstructor();

            if ($constructor && $constructor->getNumberOfParameters() > 0) {
                try {
                    // Instantiate the object first so that this instance can be used for cyclic dependencies.
                    $this->instances[$nid] = $instance = $class->newInstanceWithoutConstructor();

                    $constructorArgs = $this->resolveArgs(
                        $this->makeDefaultArgs($constructor, (array) $rule["constructorArgs"]),
                        $args,
                    );
                    $constructor->invokeArgs($instance, $constructorArgs);
                } catch (\Throwable $ex) {
                    unset($this->instances[$nid]);
                    throw $ex;
                }
            } else {
                $this->instances[$nid] = $instance = new $class->name();
            }
        }

        // Call subsequent calls on the new object.
        if (isset($class) && !empty($rule["calls"])) {
            foreach ($rule["calls"] as $call) {
                [$methodName, $args] = $call;
                $method = $class->getMethod($methodName);

                $args = $this->resolveArgs($this->makeDefaultArgs($method, $args), [], $instance);

                /**
                 * @psalm-suppress UndefinedMethod
                 */
                $method->invokeArgs($instance, $args);
            }
        }

        return $instance;
    }

    /**
     * Find the class implemented by an ID.
     *
     * This tries to see if a rule exists for a normalized ID and what class it evaluates to.
     *
     * @param string $nid The normalized ID to look up.
     * @return string|null Returns the name of the class associated with the rule or **null** if one could not be found.
     */
    private function findRuleClass($nid)
    {
        if (!isset($this->rules[$nid])) {
            return null;
        } elseif (!empty($this->rules[$nid]["aliasOf"])) {
            return $this->findRuleClass($this->rules[$nid]["aliasOf"]);
        } elseif (!empty($this->rules[$nid]["class"])) {
            return $this->rules[$nid]["class"];
        }

        return null;
    }

    /**
     * Make an array of default arguments for a given function.
     *
     * @param \ReflectionFunctionAbstract $function The function to make the arguments for.
     * @param array $ruleArgs An array of default arguments specifically for the function.
     * @return array Returns an array in the form `name => defaultValue`.
     * @throws NotFoundException If a non-optional class param is reflected and does not exist.
     */
    private function makeDefaultArgs(\ReflectionFunctionAbstract $function, array $ruleArgs)
    {
        $ruleArgs = array_change_key_case($ruleArgs);
        $result = [];

        $pos = 0;
        foreach ($function->getParameters() as $i => $param) {
            $name = strtolower($param->name);
            $reflectedClass = $reflectionType = null;
            try {
                if (class_exists(\ReflectionUnionType::class) === true) {
                    $reflectionType = $param->getType();
                    if (!empty($reflectionType) && !$reflectionType instanceof \ReflectionUnionType) {
                        if (
                            method_exists($reflectionType, "isBuiltin") &&
                            !$reflectionType->isBuiltin() &&
                            method_exists($reflectionType, "getName")
                        ) {
                            $reflectedClass = new \ReflectionClass($reflectionType->getName());
                        }
                    }
                } else {
                    $reflectedClass = $param->getClass();
                }
            } catch (\ReflectionException $e) {
                // If the class is not found in the autoloader a reflection exception is thrown.
                // Unless the parameter is optional we will want to rethrow.
                if (!$param->isOptional()) {
                    $typeName = self::parameterTypeName($param);
                    $functionName = self::functionName($function);

                    throw new NotFoundException(
                        "Could not find class for required parameter $typeName for " .
                            $functionName .
                            "in the autoloader.",
                        500,
                        $e,
                    );
                }
            }

            $hasOrdinalRule = isset($ruleArgs[$pos]);

            /*if dependency is autowired and one of the dependency is a required union type parameter which is not configured we should throw an error  */
            if (
                class_exists(ReflectionUnionType::class) &&
                $reflectionType instanceof \ReflectionUnionType &&
                (!$hasOrdinalRule || empty($ruleArgs[$name])) &&
                !$param->isOptional()
            ) {
                throw new ContainerException(
                    "The required parameter " .
                        $param->name .
                        " for class " .
                        self::functionName($function) .
                        " is not defined",
                    500,
                );
            }
            $isMatchingOrdinalReference = false;
            $isMatchingOrdinalInstance = false;
            if ($hasOrdinalRule && $reflectedClass) {
                $ordinalRule = $ruleArgs[$pos];

                if ($ordinalRule instanceof Reference) {
                    $ruleClass = $ordinalRule->getName();
                    if (is_array($ruleClass)) {
                        $ruleClass = end($ruleClass);
                    }

                    if (($resolvedRuleClass = $this->findRuleClass($ruleClass)) !== null) {
                        $ruleClass = $resolvedRuleClass;
                    }

                    // The argument is a reference that matches the type hint.
                    $isMatchingOrdinalReference = is_a($ruleClass, $reflectedClass->getName(), true);
                } elseif (is_object($ordinalRule)) {
                    // The argument is an instance that matches the type hint.
                    $isMatchingOrdinalInstance = is_a($ordinalRule, $reflectedClass->getName());
                }
            }

            if (array_key_exists($name, $ruleArgs)) {
                $value = $ruleArgs[$name];
            } elseif (
                $reflectedClass &&
                $hasOrdinalRule &&
                ($isMatchingOrdinalReference || $isMatchingOrdinalInstance)
            ) {
                $value = $ruleArgs[$pos];
                $pos++;
            } elseif (
                $reflectedClass &&
                ($reflectedClass->isInstantiable() ||
                    isset($this->rules[$reflectedClass->name]) ||
                    array_key_exists($reflectedClass->name, $this->instances))
            ) {
                $value = new DefaultReference($this->normalizeID($reflectedClass->name));
            } elseif ($hasOrdinalRule) {
                $value = $ruleArgs[$pos];
                $pos++;
            } elseif ($param->isDefaultValueAvailable()) {
                $value = $param->getDefaultValue();
            } elseif ($param->isOptional()) {
                // @codeCoverageIgnoreStart
                $value = null;
                // @codeCoverageIgnoreEnd
            } else {
                $value = new RequiredParameter($param);
            }

            $result[$param->getName()] = $value;
        }

        return $result;
    }

    /**
     * Replace an array of default args with called args.
     *
     * @param array $defaultArgs The default arguments from {@link Container::makeDefaultArgs()}.
     * @param array $args The arguments passed into a creation.
     * @param mixed $instance An object instance if the arguments are being resolved on an already constructed object.
     * @return array Returns an array suitable to be applied to a function call.
     * @throws MissingArgumentException Throws an exception when a required parameter is missing.
     */
    private function resolveArgs(array $defaultArgs, array $args, $instance = null)
    {
        // First resolve all passed arguments so their types are known.
        $args = array_map(function ($arg) use ($instance) {
            return $arg instanceof ReferenceInterface ? $arg->resolve($this, $instance) : $arg;
        }, array_change_key_case($args));

        $pos = 0;
        foreach ($defaultArgs as $name => &$default) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             * KLUDGE: DefaultReference::getClass() doesn't definitely give back a class-string.
             * Something to look into during the PHP8 refactor.
             */
            $name = strtolower($name);
            $class = null;
            if (isset($args[$pos]) && is_object($default)) {
                if (method_exists($default, "getClass")) {
                    $class = $default->getClass();
                } elseif (method_exists($default, "getName")) {
                    $class = $default->getName();
                } else {
                    $class = null;
                }
            }
            if (array_key_exists($name, $args)) {
                // This is a named arg and should be used.
                $value = $args[$name];
            } elseif (
                isset($args[$pos]) &&
                (!($default instanceof DefaultReference) || empty($class) || is_a($args[$pos], $class))
            ) {
                // There is an arg at this position and it's the same type as the default arg or the default arg is typeless.
                $value = $args[$pos];
                $pos++;
            } else {
                // There is no passed arg, so use the default arg.
                $value = $default;
            }

            if ($value instanceof ReferenceInterface) {
                $value = $value->resolve($this, $instance);
            }

            $default = $value;
        }

        return $defaultArgs;
    }

    /**
     * Create an instance of a container item.
     *
     * This method either creates a new instance or returns an already created shared instance.
     *
     * @param string $nid The normalized ID of the container item.
     * @param array $args Additional arguments to pass to the constructor.
     * @return object Returns an object instance.
     */
    protected function createInstance($nid, array $args)
    {
        $rule = $this->makeRule($nid);

        // Cache the instance or its factory for future use.
        if (empty($rule["shared"])) {
            $factory = $this->makeFactory($nid, $rule);
            $instance = $factory($args);
            $this->factories[$nid] = $factory;
        } else {
            $instance = $this->createSharedInstance($nid, $rule, $args);
        }
        return $instance;
    }

    /**
     * Determine the reflection information for a callback.
     *
     * @param callable $callback The callback to reflect.
     * @return \ReflectionFunctionAbstract Returns the reflection function for the callback.
     */
    private function reflectCallback(callable $callback)
    {
        if (is_array($callback)) {
            return new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_string($callback) || $callback instanceof \Closure) {
            return new \ReflectionFunction($callback);
        } else {
            return new \ReflectionMethod($callback, "__invoke");
        }
    }

    /**
     * Return the name of a function to aid debugging.
     *
     * @param \ReflectionFunctionAbstract $function
     * @return string
     */
    protected static function functionName(\ReflectionFunctionAbstract $function): string
    {
        $functionName = $function->getName() . "()";
        if ($function instanceof \ReflectionMethod) {
            $functionName = $function->getDeclaringClass()->getName() . "::" . $functionName;
        }
        return $functionName;
    }

    /**
     * Return the type name of a parameter to aid debugging.
     *
     * @param \ReflectionParameter $param
     * @return string
     */
    protected static function parameterTypeName(\ReflectionParameter $param): string
    {
        $type = $param->getType();
        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();
        } else {
            $name = $param->getName();
        }
        return $name;
    }
}
