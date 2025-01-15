<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Container\Tests\Fixtures;

class CircleA
{
    public $ref;

    public function __construct(CircleB $b)
    {
        $this->ref = $b;
    }
}
