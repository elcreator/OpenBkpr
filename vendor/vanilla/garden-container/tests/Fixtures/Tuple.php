<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class Tuple
{
    public $a;
    public $b;

    public function __construct($a = null, $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function setA($value)
    {
        $this->a = $value;
    }

    public function setB($value)
    {
        $this->b = $value;
    }
}
