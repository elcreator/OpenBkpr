<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;
use Garden\Container\Container;
use Garden\Container\Tests\Fixtures\Db;
use Garden\Container\Tests\Fixtures\Foo;

/**
 * Tests for the container's rule factories.
 */
class RuleFactoryTest extends AbstractContainerTest
{
    /**
     * A rule's factory should be called when getting an object.
     */
    public function testBasic()
    {
        $c = new Container();

        $c->setFactory(function () use ($c) {
            return $c;
        });

        $this->assertSame($c, $c->get("a"));
    }

    /**
     * A rule's factory should be called with proper args.
     */
    public function testGetArgs()
    {
        $c = new Container();

        $c->setFactory(function ($name) {
            return $name . "!";
        });

        $this->assertSame("foo!", $c->getArgs("a", ["foo"]));
    }

    /**
     * Make sure a factory rule resolves dependencies.
     */
    public function testDependencies()
    {
        $c = new Container();

        $c->rule(self::DB)
            ->setShared(true)

            ->rule("foo")
            ->setFactory(function (Db $db) {
                return $db;
            });

        $db = $c->get(self::DB);
        $db2 = $c->get("foo");

        $this->assertSame($db, $db2);
    }

    /**
     * A shared factory should be called just once.
     */
    public function testShared()
    {
        $c = new Container();
        $count = 0;

        $c->setShared(true)->setFactory(function () use (&$count) {
            $count++;
            return new Db();
        });

        $db = $c->get("a");
        $db2 = $c->get("a");

        $this->assertSame(1, $count);
        $this->assertSame($db, $db2);
    }

    /**
     * A non-shared factory should be called each time the container is accessed.
     */
    public function testNotShared()
    {
        $c = new Container();
        $count = 0;

        $c->setShared(false)->setFactory(function () use (&$count) {
            $count++;
            return new Db();
        });

        $db = $c->get("a");
        $db2 = $c->get("a");

        $this->assertSame(2, $count);
        $this->assertNotSame($db, $db2);
    }

    /**
     * A factory should use provided constructor args.
     *
     * @param bool $shared Whether the container should be set to shared or not.
     * @dataProvider provideShared
     */
    public function testConstructorArgs($shared)
    {
        $c = new Container();

        $c->setShared($shared)
            ->setFactory(function ($foo) {
                return $foo;
            })
            ->setConstructorArgs(["foo"]);

        $foo = $c->get("foo");

        $this->assertSame("foo", $foo);
    }

    /**
     * A factory should be able to make calls if a class is provided.
     *
     * @param bool $shared Whether the container should be set to shared or not.
     * @dataProvider provideShared
     */
    public function testCalls($shared)
    {
        $dic = new Container();

        $dic->setShared($shared)
            ->setFactory([self::FOO, "create"])
            ->addCall("setFoo", ["foo"])
            ->addCall("setBar", ["bar"]);

        /* @var \Garden\Container\Tests\Fixtures\Foo $foo */
        $foo = $dic->get(self::FOO);
        $this->assertSame("foo", $foo->foo);
        $this->assertSame("bar", $foo->bar);
    }

    /**
     * Test that the a factory cache is cleared after a rule change.
     */
    public function testCacheClearOnRule()
    {
        $dic = new Container();

        $dic->rule(Foo::class)
            ->setShared(false)
            ->addCall("setFoo", ["foo1"])
            ->addCall("setFoo", ["foo2"]);

        /** @var Foo $foo */
        $foo = $dic->get(Foo::class);
        $this->assertEquals("foo2", $foo->foo);

        $dic->rule(Foo::class)->addCall("setFoo", ["foo3"]);

        /** @var Foo $foo */
        $foo = $dic->get(Foo::class);
        $this->assertEquals("foo3", $foo->foo);
    }
}
