<?php

/**
 * @author Sooraj FRancis <sfrancis@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class UnionTypeBasicWithoutDefault
{
    public int|float $a;
    public string $b;

    public function __construct(int|float $a, string $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}
