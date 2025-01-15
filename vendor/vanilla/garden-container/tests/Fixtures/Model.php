<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class Model
{
    public $sql;

    public function __construct(Sql $sql)
    {
        $this->sql = $sql;
    }

    public function setSql(Sql $sql)
    {
        $this->sql = $sql;
    }
}
