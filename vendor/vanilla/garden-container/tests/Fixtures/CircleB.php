<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Container\Tests\Fixtures;

class CircleB
{
    public $ref;

    public function __construct(CircleC $c)
    {
        $this->ref = $c;
    }
}
