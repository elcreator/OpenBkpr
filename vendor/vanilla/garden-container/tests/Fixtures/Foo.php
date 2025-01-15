<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class Foo implements FooAwareInterface
{
    public $foo;
    public $bar;

    // Do not add a constructor.

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }

    public static function create()
    {
        return new Foo();
    }
}
