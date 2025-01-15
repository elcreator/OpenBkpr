<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

/**
 * Set the name on a db.
 *
 * @param Db $db The db to set.
 * @param string $name The new name.
 */
function setDbName(Db $db, $name)
{
    $db->name = $name;
}
