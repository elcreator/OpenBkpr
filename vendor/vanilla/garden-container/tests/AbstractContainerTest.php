<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;

use Garden\Container\Tests\Fixtures\Db;
use Garden\Container\Tests\Fixtures\DbDecorator;
use Garden\Container\Tests\Fixtures\DbInterface;
use Garden\Container\Tests\Fixtures\Foo;
use Garden\Container\Tests\Fixtures\FooAwareInterface;
use Garden\Container\Tests\Fixtures\PdoDb;
use Garden\Container\Tests\Fixtures\Sql;
use Garden\Container\Tests\Fixtures\UnionTypeBasicWithDefaults;
use Garden\Container\Tests\Fixtures\UnionTypeBasicWithoutDefault;
use Garden\Container\Tests\Fixtures\UnionTypeComplex;
use PHPUnit\Framework\TestCase;

abstract class AbstractContainerTest extends TestCase
{
    const DB = Db::class;
    const DB_INTERFACE = DbInterface::class;
    const DB_DECORATOR = DbDecorator::class;
    const FOO = Foo::class;
    const FOO_AWARE = FooAwareInterface::class;
    const PDODB = PdoDb::class;
    const SQL = Sql::class;
    const UNION_BASIC_DEFAULTS = UnionTypeBasicWithDefaults::class;
    const UNION_BASIC = UnionTypeBasicWithoutDefault::class;
    const UNION_COMPLEX = UnionTypeComplex::class;

    /**
     * Provide values for tests that are configured for shared and non shared.
     *
     * @return array Returns a data provider array.
     */
    public function provideShared()
    {
        return [
            "notShared" => [false],
            "shared" => [true],
        ];
    }
}
