<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class Sql
{
    public $db;
    public $name;

    public function __construct(Db $db = null, $name = "Sql")
    {
        $this->db = $db;
        $this->name = $name;
    }

    public function setDb(Db $db)
    {
        $this->db = $db;
    }
}
