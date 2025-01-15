<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class FooConsumer
{
    public $foo;

    public function __construct(FooAwareInterface $foo)
    {
        $this->foo = $foo;
    }
}
