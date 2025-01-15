<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;

use Garden\Container\Container;
use Garden\Container\Tests\Fixtures\Db;

/**
 * Test basic rule access.
 */
class RuleAccessTest extends AbstractContainerTest
{
    /**
     * A new container's default rule should have sensible defaults.
     */
    public function testDefaultRule()
    {
        $c = new Container();

        $this->assertSame("", $c->getClass());
        $this->assertSame(true, $c->getInherit());
        $this->assertSame(false, $c->isShared());
        $this->assertSame([], $c->getConstructorArgs());
        $this->assertNull($c->getFactory());
        $this->assertSame("", $c->getAliasOf());
        $this->assertSame([], $c->getAliases());

        return $c;
    }

    /**
     * Getters and setters should work.
     *
     * @param Container $c A default container.
     * @depends testDefaultRule
     */
    public function testGettersSetters(Container $c)
    {
        $r = $c->setClass(self::FOO);
        $this->assertSame(self::FOO, $c->getClass());
        $this->assertSame($c, $r);

        $r = $c->setInherit(false);
        $this->assertSame(false, $c->getInherit());
        $this->assertSame($c, $r);

        $r = $c->setShared(true);
        $this->assertSame(true, $c->isShared());
        $this->assertSame($c, $r);

        $arr = [123];
        $r = $c->setConstructorArgs($arr);
        $this->assertSame($arr, $c->getConstructorArgs());
        $this->assertSame($c, $r);

        $callback = function () {
            return "foo";
        };
        $r = $c->setFactory($callback);
        $this->assertSame($callback, $c->getFactory());
        $this->assertSame($c, $r);

        $alias = "bar";
        $r = $c->setAliasOf($alias);
        $this->assertSame($alias, $c->getAliasOf());
        $this->assertSame($c, $r);
    }

    /**
     * A new container should have the default (*) rule.
     */
    public function testHasDefaultRule()
    {
        $c = new Container();

        $this->assertTrue($c->hasRule("*"));
    }

    /**
     * The container should report that any class that exists is in the container.
     */
    public function testContainerHasExistingClass()
    {
        $dic = new Container();

        $this->assertTrue($dic->has(self::DB));
    }

    /**
     * The container should report that any rule name exists in the container.
     */
    public function testContainerHasRule()
    {
        $dic = new Container();

        $dic->rule("foo")->setClass(self::DB);

        $this->assertTrue($dic->has("foo"));
    }

    /**
     * The container should not have a rule just because it is simply selected.
     */
    public function testContainerDoesNotHaveEmptyRule()
    {
        $dic = new Container();

        $dic->rule("foo");

        $this->assertFalse($dic->has("foo"));
    }

    /**
     * The container should contain named instances.
     */
    public function testContainerHasInstance()
    {
        $dic = new Container();

        $dic->setInstance("foo", new Db());

        $this->assertTrue($dic->has("foo"));
    }

    /**
     * A defined rule should test for existence.
     */
    public function testHasRule()
    {
        $c = new Container();

        $this->assertFalse($c->hasRule("foo"));

        $c->rule("foo")->setClass(self::DB);
        $this->assertTrue($c->hasRule("foo"));
    }

    /**
     * A newly defined rule should not test as true.
     */
    public function testNotHasNewSelectedRule()
    {
        $c = new Container();

        $this->assertFalse($c->hasRule("foo"));

        $c->rule("foo");
        $this->assertFalse($c->hasRule("foo"));
    }

    /**
     * A subclass should not say it has a rule if its parent has a rule defined.
     */
    public function testSubclassHasRule()
    {
        $c = new Container();

        $c->rule(self::DB)->setClass(self::PDODB);

        $this->assertFalse($c->hasRule(self::PDODB));
    }

    /**
     * Added aliases should be discoverable.
     */
    public function testAddAliases()
    {
        $dic = new Container();

        $r = $dic
            ->rule("foo")
            ->addAlias("bar")
            ->addAlias("baz");

        $this->assertEmpty(array_diff(["bar", "baz"], $dic->getAliases()));
        $this->assertSame($dic, $r);
    }

    /**
     * Test the symmetry of {@link Container::addAlias()} and {@link Container::getAliasOf()}.
     */
    public function testAddAliasAndAliasOfSymmetry()
    {
        $dic = new Container();

        $dic->rule("foo")->addAlias("bar");

        $this->assertSame("foo", $dic->rule("bar")->getAliasOf());
    }

    /**
     * Test the symmetry of {@link Container::setAliasOf()} and {@link Container::getAliases()}.
     */
    public function testSetAliasOfAndGetAliasesSymmetry()
    {
        $dic = new Container();

        $dic->rule("foo")->setAliasOf("bar");

        $this->assertSame(["foo"], $dic->rule("bar")->getAliases());
    }

    /**
     * Setting an alias to yourself should do nothing and raise a notice.
     */
    public function testSetSameAliasNotice()
    {
        $dic = new Container();
        $dic->rule("foo");

        $this->expectNotice();
        $dic->setAliasOf("foo");

        // AbstractContainerTest has custom error assertions that aren't actually assertions.
        $this->assertTrue(true);
    }

    /**
     * Removing an alias of a different rule should generate a notice.
     */
    public function testRemoveDifferentAliasNotice()
    {
        $dic = new Container();

        $this->expectNotice();
        $r = $dic
            ->rule("foo")
            ->addAlias("bar")
            ->rule("baz")
            ->removeAlias("bar");

        $this->assertSame([], $dic->rule("foo")->getAliases());
        $this->assertSame($dic, $r);
    }

    /**
     * Setting an alias to yourself should do nothing and raise a notice.
     */
    public function testAddSameAliasNotice()
    {
        $dic = new Container();

        $this->expectNotice();
        $r = $dic->rule("foo")->addAlias("foo");

        $this->assertSame($dic, $r);
    }

    /**
     * You should be able to remove a rule alias.
     */
    public function testRemoveAlias()
    {
        $dic = new Container();

        $dic->addAlias("foo");
        $this->assertSame(["foo"], $dic->getAliases());

        $dic->removeAlias("foo");
        $this->assertSame([], $dic->getAliases());
    }

    /**
     * Aliases should be normalized to exclude leading backslashes.
     */
    public function testAliasNormalization()
    {
        $dic = new Container();

        $dic->rule("foo")->addAlias("\bar");

        $this->assertSame(["bar"], $dic->getAliases());
    }
}
