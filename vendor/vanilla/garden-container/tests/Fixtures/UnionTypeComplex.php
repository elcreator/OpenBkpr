<?php

/**
 * @author Sooraj FRancis <sfrancis@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class ParentClass
{
}
class ChildClass extends ParentClass
{
}

class UnionTypeComplex
{
    public string|ChildClass $a;
    public string $b;
    public function __construct(string|ChildClass $a, string|null $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}
