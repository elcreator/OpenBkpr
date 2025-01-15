<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;

use Garden\Container\Container;
use Garden\Container\Tests\Fixtures\Db;
use Garden\Container\Tests\Fixtures\DbInterface;
use Garden\Container\Tests\Fixtures\PdoDb;

/**
 * A class to test specific issues that are discovered in the wild.
 */
class IssuesTest extends AbstractContainerTest
{
    public function testCallOnInterfacedSubclass()
    {
        $dic = new Container();
        $dic->setShared(true);

        $dic->rule(DbInterface::class)
            ->setClass(PdoDb::class)
            ->addCall("inc");

        /* @var PdoDb $db */
        $db = $dic->get(DbInterface::class);

        $this->assertSame(1, $db->i);
    }

    public function testCallWithCircularReference()
    {
        $dic = new Container();
        $dic->setShared(true);

        $dic->rule(Db::class)
            ->setClass(PdoDb::class)
            ->addCall("inc")
            ->addCall("nameDb", ["foo"]);

        /* @var Db $db */
        $db = $dic->get(Db::class);

        $this->assertSame(1, $db->i);
        $this->assertSame("foo", $db->name);
    }
}
