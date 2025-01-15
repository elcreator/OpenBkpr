<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;

use Garden\Container\Callback;
use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Container\Reference;
use Garden\Container\Tests\Fixtures\Bad;
use Garden\Container\Tests\Fixtures\ChildClass;
use Garden\Container\Tests\Fixtures\CircleA;
use Garden\Container\Tests\Fixtures\CircleB;
use Garden\Container\Tests\Fixtures\CircleC;
use Garden\Container\Tests\Fixtures\Db;
use Garden\Container\Tests\Fixtures\Sql;
use Garden\Container\Tests\Fixtures\Tuple;
use Foo\NotFound;

class ContainerTest extends AbstractContainerTest
{
    public function testBasicConstruction()
    {
        $dic = new Container();
        $db = $dic->get(self::DB);

        $this->assertInstanceOf(self::DB, $db);
    }

    public function testNotSharedConstruction()
    {
        $dic = new Container();

        $db1 = $dic->get(self::DB);
        $db2 = $dic->get(self::DB);
        $this->assertNotSame($db1, $db2);
    }

    public function testSharedConstuction()
    {
        $dic = new Container();

        $dic->setShared(true);
        $db1 = $dic->get(self::DB);
        $db2 = $dic->get(self::DB);
        $this->assertSame($db1, $db2);
    }

    public function testConstrucWithPassedArgs()
    {
        $dic = new Container();

        /** @var Db $db */
        $db = $dic->getArgs(self::DB, ["foo"]);
        $this->assertSame("foo", $db->name);
    }

    public function testConstructDifferentClass()
    {
        $dic = new Container();

        $dic->rule(self::DB)->setClass(self::PDODB);

        $db = $dic->get(self::DB);
        $this->assertInstanceOf(self::DB, $db);
    }

    public function testBaicInjection()
    {
        $dic = new Container();

        /* @var Sql $sql */
        $sql = $dic->get(self::SQL);
        $this->assertInstanceOf(self::DB, $sql->db);
    }

    public function testSetInstance()
    {
        $dic = new Container();

        $db = new Db();

        $dic->setInstance(get_class($db), $db);
        $db2 = $dic->get(get_class($db));

        $this->assertSame($db, $db2);
    }

    /**
     * Test rule calls.
     *
     * @param bool $shared Whether the container should be shared.
     * @dataProvider provideShared
     */
    public function testCalls($shared)
    {
        $dic = new Container();

        $dic->rule(Tuple::class)
            ->setShared($shared)
            ->setConstructorArgs(["a", "b"])
            ->addCall("setA", ["foo"]);

        $t = $dic->get(Tuple::class);
        $this->assertSame("foo", $t->a);
        $this->assertSame("b", $t->b);
    }

    /**
     * Test instantiation on classes without constructors.
     *
     * @param bool $shared Whether the container should be shared.
     * @dataProvider provideShared
     */
    public function testNoConstructor($shared)
    {
        $dic = new Container();

        $dic->setShared($shared);

        $o = $dic->get(self::FOO);
        $this->assertInstanceOf(self::FOO, $o);
    }

    public function testNestedReference()
    {
        $parent = new Container();
        $child = new Container();

        $parent->setInstance("config", $child);
        $child->setInstance("foo", "bar");

        $parent->rule(self::DB)->setConstructorArgs([new Reference(["config", "foo"])]);

        $db = $parent->get(self::DB);
        $this->assertSame("bar", $db->name);
    }

    /**
     * Test interface call rules.
     */
    public function testInterfaceCalls()
    {
        $dic = new Container();

        $dic->rule(self::FOO_AWARE)->addCall("setFoo", [123]);

        $foo = $dic->get(self::FOO);
        $this->assertSame(123, $foo->foo);
    }

    /**
     * Interface calls should stack with rule calls.
     */
    public function testInterfaceCallMerging()
    {
        $dic = new Container();

        $dic->rule(self::FOO_AWARE)
            ->addCall("setFoo", [123])
            ->defaultRule()
            ->addCall("setBar", [456]);

        $foo = $dic->get(self::FOO);
        $this->assertSame(123, $foo->foo);
        $this->assertSame(456, $foo->bar);
    }

    /**
     * A named arg should override injections.
     */
    public function testNamedInjectedArg()
    {
        $dic = new Container();

        $db = new Db();
        /* @var Sql $sql */
        $sql = $dic->getArgs(self::SQL, ["db" => $db]);
        $this->assertSame($db, $sql->db);
    }

    /**
     * A positional arg should override injections.
     */
    public function testPositionalInjectedArg()
    {
        $dic = new Container();

        $db = new Db();
        /* @var Sql $sql */
        $sql = $dic->getArgs(self::SQL, [$db]);
        $this->assertSame($db, $sql->db);
    }

    /**
     * Trying to get a non-existent, unset name should throw a not found exception.
     *
     * @param bool $shared Set the container to shared or not.
     * @dataProvider provideShared
     */
    public function testNotFoundException($shared)
    {
        $dic = new Container();

        $this->expectException(NotFoundException::class);
        $dic->setShared($shared)->get("adsf");
    }

    /**
     * Test {@link Container::call()}
     */
    public function testBasicCall()
    {
        $dic = new Container();

        /**
         * @var Sql $sql
         */
        $sql = $dic->getArgs(self::SQL, ["db" => null]);
        $this->assertNull($sql->db);

        $dic->call([$sql, "setDb"]);
        $this->assertInstanceOf(self::DB, $sql->db);
    }

    /**
     * Test {@link Container::call()} with a closure.
     */
    public function testCallClosure()
    {
        $dic = new Container();

        $called = false;

        $dic->call(function (Db $db) use (&$called) {
            $called = true;
            $this->assertInstanceOf(self::DB, $db);
        });

        $this->assertTrue($called);
    }

    /**
     * Global functions should be callable with {@link Container::call()}.
     */
    public function testCallFunction()
    {
        require_once __DIR__ . "/Fixtures/functions.php";

        $dic = new Container();
        $dic->setShared(true);

        $dic->call("Garden\Container\Tests\Fixtures\setDbName", ["func"]);

        /* @var Db $db */
        $db = $dic->get(self::DB);
        $this->assertSame("func", $db->name);
    }

    /**
     *
     */
    public function testCallback()
    {
        $dic = new Container();

        $i = 1;
        $cb = new Callback(function () use (&$i) {
            return $i++;
        });

        /**
         * @var Tuple $tuple
         */
        $tuple = $dic->getArgs(Tuple::class, [$cb, $cb]);

        $this->assertSame(1, $tuple->a);
        $this->assertSame(2, $tuple->b);
    }

    /**
     * An rule alias should point to the same rule.
     */
    public function testAlias()
    {
        $dic = new Container();

        $dic->rule("foo")->setAliasOf(self::DB);

        $db = $dic->get("foo");
        $this->assertInstanceOf(self::DB, $db);
    }

    /**
     * Test multiple aliases.
     */
    public function testMultipleAliases()
    {
        $dic = new Container();

        $aliases = ["foo", "bar", "baz"];
        $dic->rule(Db::class)->addAlias(...$aliases);

        foreach ($aliases as $alias) {
            $o = $dic->get($alias);
            $this->assertInstanceOf(Db::class, $o);
        }
    }

    /**
     * An alias to a shared rule should get an instance of the exact same object.
     */
    public function testSharedAlias()
    {
        $dic = new Container();

        $dic->rule(self::DB)
            ->setShared(true)
            ->addAlias("foo");

        $db1 = $dic->get(self::DB);
        $db2 = $dic->get("foo");
        $this->assertSame($db1, $db2);
    }

    /**
     * The container should be case-insensitive to classes that exist.
     */
    public function testCaseInsensitiveClass()
    {
        require_once __DIR__ . "/Fixtures/Db.php";

        $dic = new Container();

        $className = strtolower(self::DB);
        $this->assertNotSame($className, self::DB);

        /* @var Db $db */
        $db = $dic->get($className);
        $this->assertInstanceOf(self::DB, $db);
    }

    /**
     * An interface should be able to mark rules ahred.
     */
    public function testSharedInterface()
    {
        $dic = new Container();

        $dic->rule(self::DB_INTERFACE)->setShared(true);

        $db1 = $dic->get(self::DB);
        $db2 = $dic->get(self::DB);

        $this->assertSame($db1, $db2);
    }

    /**
     * An interface should not override a class shared rule.
     *
     * @param bool $shared Whether or not the interface should be shared.
     * @dataProvider provideShared
     */
    public function testClassInterfaceSharedPrecedence($shared)
    {
        $dic = new Container();

        $dic->rule(self::DB)
            ->setShared($shared)

            ->rule(self::DB_INTERFACE)
            ->setShared(!$shared);

        $db1 = $dic->get(self::DB);
        $db2 = $dic->get(self::DB);

        if ($shared) {
            $this->assertSame($db1, $db2);
        } else {
            $this->assertNotSame($db1, $db2);
        }
    }

    /**
     * The container should not have an interface.
     */
    public function testDoesNotHaveInterface()
    {
        $dic = new Container();

        $this->assertFalse($dic->has(self::DB_INTERFACE));
    }

    /**
     * Test cloning with rules.
     */
    public function testCloning()
    {
        $dic = $dic = new Container();
        $dic->rule(self::DB)->setShared(true);

        $dic2 = clone $dic;
        $dic2->rule(self::DB)->setConstructorArgs(["foo"]);

        $db1 = $dic->get(self::DB);
        $db2 = $dic2->get(self::DB);

        $this->assertNotSame($db1, $db2);
        $this->assertNotSame("foo", $db1->name);
    }

    /**
     * Test dumping container instances.
     */
    public function testClearing()
    {
        $dic = new Container();
        $dic->get(self::DB);

        $dic->clearInstances();
        $this->assertFalse($dic->hasInstance(self::DB));
    }

    /**
     * There was a bug when args aren't specified, but it wasn't caught because tests suppressed notices.
     */
    public function testNullArgsBug()
    {
        $dic = new Container();
        $sql = new Sql();

        $dic->call([$sql, "setDb"], []);
        // Assert that no exception were thrown.
        $this->assertTrue(true);
    }

    /**
     * Test circular references on shared objects.
     *
     * @param string $id The ID to get from the container.
     * @dataProvider provideCircularIDs
     */
    public function testCircularReference($id)
    {
        $dic = new Container();
        $dic->setShared(true);

        $o = $dic->get($id);
        $this->assertInstanceOf($id, $o);
        $this->assertSame($o, $o->ref->ref->ref);
    }

    /**
     * Provide test data for {@link testCircularReference()}.
     *
     * @return array Returns a data provider array.
     */
    public function provideCircularIDs()
    {
        return [
            "a" => [CircleA::class],
            "b" => [CircleB::class],
            "c" => [CircleC::class],
        ];
    }

    public function testContainerCorruption()
    {
        $dic = new Container();
        $dic->setShared(true);
        $dic->rule(Tuple::class)->setConstructorArgs(["a" => "a", "b" => new Reference(NotFound::class)]);

        try {
            $tuple = $dic->get(Tuple::class);
            $this->fail("This test is supposed to throw an exception!");
        } catch (\Exception $ex) {
            $this->assertFalse($dic->hasInstance(Tuple::class));
            return;
        }
    }

    /**
     * Test for class with union type arguments with default value
     */

    public function testBasicUnionTypeWithDefaultArgs()
    {
        if (phpversion() < 8) {
            $this->markTestSkipped("Not Applicable for PHP Version less than 8");
        }
        $dic = new Container();
        $uType = $dic->get(self::UNION_BASIC_DEFAULTS);

        //Verify that the instance is created with default values

        $this->assertInstanceOf(self::UNION_BASIC_DEFAULTS, $uType);
        $this->assertSame(2, $uType->a);
        $this->assertSame("hello", $uType->b);

        // Veify that the defaults gets overwritten if we apply specific rules
        $dic2 = new Container();
        $dic2
            ->rule(self::UNION_BASIC_DEFAULTS)
            ->setConstructorArgs([2.375, "garden"])
            ->setShared(false);

        $uType = $dic2->get(self::UNION_BASIC_DEFAULTS);
        $this->assertInstanceOf(self::UNION_BASIC_DEFAULTS, $uType);
        $this->assertSame(2.375, $uType->a);
        $this->assertSame("garden", $uType->b);
    }

    /**
     * Test for class with basic union type arguments
     */
    public function testBasicUnionType()
    {
        if (phpversion() < 8) {
            $this->markTestSkipped("Not Applicable for PHP Version less than 8");
        }
        $dic = new Container();

        //When auto wiring and if the union type argument is not explicitly defined then throw error

        $this->expectExceptionCode(500);
        $this->expectException(ContainerException::class);
        $uType = $dic->get(self::UNION_BASIC);

        $dic->rule(self::UNION_BASIC)->setConstructorArgs([2.375, "garden"]);

        //Verify that the values are applied
        $uType = $dic->get(self::UNION_BASIC);
        $this->assertSame(2.375, $uType->a);
        $this->assertSame("garden", $uType->b);
    }

    /**
     * Test for class with complex union type
     */
    public function testComplexUnionTypes()
    {
        if (phpversion() < 8) {
            $this->markTestSkipped("Not Applicable for PHP Version less than 8");
        }
        $dic = new Container();

        //When auto wiring and if the union type argument is not explicitly defined then throw error
        $this->expectExceptionCode(500);
        $this->expectException(ContainerException::class);
        $uType = $dic->get(self::UNION_COMPLEX);

        // add rule for the class and add constructor arguments
        $a = new Reference(ChildClass::class);
        $dic->rule(self::UNION_COMPLEX)->setConstructorArgs([$a, null]);
        $uType = $dic->get(self::UNION_COMPLEX);

        $this->assertInstanceOf(self::UNION_COMPLEX, $uType);
        $this->assertInstanceOf(ChildClass::class, $uType->a);
        $this->assertNull($uType->b);
    }
}
