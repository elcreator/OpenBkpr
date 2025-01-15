# Garden Container

[![Build Status](https://img.shields.io/travis/vanilla/garden-container.svg?style=flat)](https://travis-ci.com/vanilla/garden-container)
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/vanilla/garden-container.svg?style=flat)](https://scrutinizer-ci.com/g/vanilla/garden-container/)
[![Packagist Version](https://img.shields.io/packagist/v/vanilla/garden-container.svg?style=flat)](https://packagist.org/packages/vanilla/garden-container)
![MIT License](https://img.shields.io/packagist/l/vanilla/garden-container.svg?style=flat)
[![CLA](https://cla-assistant.io/readme/badge/vanilla/garden-container)](https://cla-assistant.io/vanilla/garden-container)

The Garden Container is a simple, but powerful dependency injection container.

## Features

-   Automatically wire dependencies using parameter type hints. You get a lot of functionality before you've done any configuration.
-   Create shared instances of objects without having to use difficult to test statics.
-   Dependencies can be configured for base classes and interfaces and shared amongst subclasses.
-   Setter injection can be configured for classes in the container.
-   Dependencies can be configured to reference sub-containers. Use the container to inject properties from your config files.
-   You can change the classes that implement dependencies or specify the definitive class for an interface.
-   Objects can be constructed with custom factory functions to handle refactoring or edge-cases.

## The Basics of Dependency Injection

Consider the following simple object structure where a controller object depends on a Model object and that Model object depends on a database connection.

```php
class Controller
{
    public function __construct(Model $model)
    {
    }
}

class Model
{
    public function __construct(PDO $db)
    {
    }
}
```

In order to use the controller you'd have to do a fair amount of construction.

```php
$controller = new Controller(new Model(new PDO($dsn, $username, $password);
```

You can see how this can get messy when you have to create a lot objects or deep object hierarchies. With a dependency injection container you don't have to do any of that.

```php
$dic = new Container();
$controller = $dic->get("Controller"); // dependencies magically wired up
```

The container inspects the objects its constructing for type hints and will then construct those objects by recursing back into the container. This is called **auto-wiring** and allows you to create any number of complex object graphs in a very simple manner. If you want to later add more dependencies then you can just add a parameter to your constructor and it will be resolved automatically.

A well designed application will rely heavily on auto-wiring and configure the container only for a few dependencies.

## Configuring the Container with Rules

You can override the behaviour of any class's instantiation using rules. To configure a rule for a class you use the `rule()` method to select the rule and then any of the various rule getters and setters.

### Namespaces

Rules are usually named with the name of the class that you will want to get from the container. If you are using namespaces then rules must be named with the fully qualified name of the class. The name can start with a forward slash, but it will be stripped before being processed.

PHP 5.6 introduced the `::class` construct which is a useful way to specify class names for the container.

### Case-Sensitivity

The container should be thought of as case-sensitive, however if you try and fetch a class with incorrect casing then the container will be able to find the class if the class is already included or the autoloader is case-insensitive. Since most PSR autoloaders are case-sensitive you are risking bugs if you are sloppy with casing in the container.

## Constructor Args

Auto-wiring works only for type-hinted parameters, but if a class has other parameters you will have to configure them using the `setConstructorArgs()` method.

```php
$dic = new Container();
$dic->rule("PDO")->setConsructorArgs([$dsn, $username, $password]);
```

Here new PDO instances will be configured with the proper credentials. A great benefit of this is that the container passes along the configuration only when a new object is retrieved from the container.

### Mixing Type-Hinted and Non-Type-Hinted Constructor Arguments

If a class has some type hints and some regular parameters you only specify the non-type-hinted ones with constructor args. The other ones will be auto-wired by the container.

```php
class Job
{
    public function __construct(Envornment $env, $name, Logger $log)
    {
    }
}

$dic = new Container();
$dic->rule("Job")->setConstructorArgs(["job name"]);

$job = $dic->get("Job");
```

### Named Arguments

When passing an arguments array to any of the container's methods that expect arguments you can use the array keys to match to a specific parameter name. This is useful if you want to specify a specific argument later in the parameters list. You can also override a type-hinted parameter by specifying its name.

```php
$dic->rule("Job")->setConstructorArgs([
    "name" => "job name",
    "log" => $dic->get("SysLogger"),
]);
```

### Passing Constructor Arguments During Object Creation

You can pass some or all constructor arguments with `getArgs()`.

```php
$dic = new Container();
$pdo = $dic->getArgs("PDO", [$dsn, $username, $password]);
```

## Shared Objects

You mark a class as shared which means that the container will return the same instance whenever the class is requested. This is a much better alternative to global variables or singletons.

```php
$dic = new Container();

$dic->rule("PDO")
    ->setConsructorArgs([$dsn, $username, $password])
    ->setShared(true);

$db1 = $dic->get("PDO");
$db2 = $dic->get("PDO");
// $db1 === $db2
```

## Setter Injection with Calls

You can add method calls to a rule. Each call that is added is called in order after the object is first created. Calls work in much the same way that constructors do so they will also auto-wire if there are type-hinted parameters.

```php
$dic = new Container();

$dic->rule("PDO")
    ->setConsructorArgs([$dsn, $username, $password])
    ->addCall("setAttribute", [PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION])
    ->addCall("setAttribute", [PDO::MYSQL_ATTR_INIT_COMMAND, "set names utf8"]);
```

## Specifying the Class of a Rule

You can use the `setClass()` method specify the class that is created when getting an item from the container. This is useful when you want to specify a specific subclass of an abstract base class or interface to satisfy dependencies. Rules also don't have to represent an actual class; in this case you must specify the class.

```php
$dic = new Container();

$dic->rule("Psr\Log\LoggerInterface")->setClass("SysLogger");
```

## Rule Inheritance

By default, all subclasses will inherit rules from their base class. In this way you can define rules for just the base class. If you don't want subclasses to inherit rules then you can override this behavior with `setInherit()`.

```php
class Model {
    ...
}

class UserModel extends Model {
    ...
}

$dic->rule('Model')
    ->setShared(true);

$um1 = $dic->get('UserModel');
$um2 = $dic->get('UserModel');
// $um1 === $um2
```

### Interface Inheritance

Rules can inherit from interfaces in a limited way. If you define a rule on an interface, any classes that implement it will call its method calls in addition to their own and also use the interface rule's constructor args if it doesn't have any defined itself.

```php
$dic->rule("Psr\Log\LogAwareInterface")->addCall("setLogger");
```

### The Default Rule

There is a default rule that rules inherit from. You can modify this rule by selecting it with either the `defaultRule()` method or `rule('*')`.

```php
$dic->defaultRule()->setShared(true);
// Now all objects are shared by default.
```

## Reference Dependencies

You can specify arguments that reference back into the container. To do this you specify arguments as `Reference` objects. You construct a reference object with an array where each item is a key into the container or a sub-container.

```php
class Config
{
    public function __construct($path)
    {
        $this->data = json_decode(file_get_contents($path), true);
    }

    public function get($key)
    {
        return $this->data[$key];
    }
}

$dic = new Container();

$dic->rule(Config::class)
    ->setShared(true)
    ->setConstructorArgs(["../config.json"])

    ->rule(PDO::class)
    ->setConstructorArgs([
        new Reference([Config::class, "dsn"]),
        new Reference([Config::class, "user"]),
        new Reference([Config::class, "password"]),
    ]);

$pdo = $dic->get(PDO::class);
```

In the above example the PDO object will be constructed with information provided from the Config object in the container. Each reference specifies the `Config::class` first so the container looks for that first, then it calls `get()` with the next item in the reference's array.

### The ReferenceInterface

The `Garden\Container` namespace defines a `ReferenceInterface` that you can implement to satisfy dependencies with custom references. There is also the `Callback` class that you can construct to satisfy a reference with a callable argument.

## Setting Specific Instances in the Container

You can set a specific object instance to the container with the `setInstance()` method. When you do this the object will always be shared. One use for setInstance is to put the container into itself so that it can be a dependency. This is considered an anti-pattern by some, but can be necessary.

```php
class Dispatcher
    public function __construct(Container $dic) {
        $this->dic = $dic;
    }

    public function dispatch($url) {
        $args = explode('/', $url);
        $controllerName = ucfirst(array_shift($args)).'Controller';
        $method = array_shift($args) ?: 'index';

        $controller = $this->dic->get($controllerName);

        return $this->dic->call([$controller, $method], $args)
    }
}

$dic = new Container();
$dic->setInstance(Container::class, $dic);

$dispatcher = $dic->get(Dispatcher::class);
$dispatcher->dispatch(...);
```

The `call()` method is similar to [call_user_func_array](http://php.net/manual/en/function.call-user-func-array.php), but is called through the container so dependencies are auto-wired just like other methods.

## Aliases

You can specify a rule to be an alias of another rule. Calling get() on the alias is the same as calling get() on the rule it aliases. The following methods are used to define aliases.

-   **getAliasOf(), setAliasOf()**. These methods will make the current rule alias another rule. Not that rules that are aliases will ignore other settings because they are fetched from the destination rule.

-   **addAlias(), removeAlias(), getAliases()**. These methods will add an alias to the current rule. These methods are often more convenient because you usually want to configure a rule and set aliases at the same time.

### Why Use Aliases?

Aliases are useful when you have dependencies inconsistently type-hinted between base classes, classes, or interfaces and you want them all to resolve to the same shared instance.

```php
class Task
{
    public function __construct(LoggerInterface $log)
    {
    }
}

class Item
{
    public function __construct(AbstractLogger $log)
    {
    }
}

$dic = new Container();

$dic->rule(LoggerInterface::class)
    ->setClass("SysLogger")
    ->setShared(true)
    ->addAlias(AbstractLogger::class);

$task = $dic->get(Task::class);
$item = $dic->get(Item::class);
// Both logs will point to the same shared instance.
```

## Acknowledgements

This project is heavily inspired by the excellent [DICE](https://github.com/Level-2/Dice) and to a lesser extent [Aura.Di](https://github.com/auraphp/Aura.Di) projects. Any code in the Garden Container that resembles those projects probably is from them and remains the copyright of the respective owners. The developers of those projects are much more clever than we are.
