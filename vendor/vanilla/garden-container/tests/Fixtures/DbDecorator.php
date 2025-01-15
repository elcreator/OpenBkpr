<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Container\Tests\Fixtures;

class DbDecorator implements DbInterface
{
    /**
     * @var DbInterface
     */
    public $db;

    public function __construct(DbInterface $db = null)
    {
        if ($db === null) {
            $this->db = new Db("default");
        } else {
            $this->db = $db;
        }
    }
}
