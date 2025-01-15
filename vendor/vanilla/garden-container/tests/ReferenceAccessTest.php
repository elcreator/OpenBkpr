<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests;

use Garden\Container\Callback;
use Garden\Container\Container;
use Garden\Container\DefaultReference;
use Garden\Container\Reference;

class ReferenceAccessTest extends AbstractContainerTest
{
    public function testCallbackAccess()
    {
        $f = function () {
            return true;
        };

        $cb = new Callback($f);
        $this->assertSame($f, $cb->getCallback());

        $f2 = function () {
            return false;
        };
        $cb->setCallback($f2);
        $this->assertSame($f2, $cb->getCallback());
    }

    public function testReferenceAccess()
    {
        $name = "foo";

        $r = new Reference($name);
        $this->assertSame($name, $r->getName());

        $name2 = "bar";
        $r->setName($name2);
        $this->assertSame($name2, $r->getName());
    }

    public function testDefaultReferenceAccess()
    {
        $name = "foo";

        $r = new DefaultReference($name);
        $this->assertSame($name, $r->getClass());

        $name2 = "bar";
        $r->setClass($name2);
        $this->assertSame($name2, $r->getClass());
    }

    public function testEmptyReferenceResolution()
    {
        $c = new Container();
        $r = new Reference("");

        $this->assertNull($r->resolve($c));
    }

    public function testStringReferenceResolution()
    {
        $c = new Container();
        $c->setInstance("foo", "bar");
        $r = new Reference("foo");

        $this->assertSame("bar", $r->resolve($c));
    }

    public function testSingleArrayReference()
    {
        $c = new Container();
        $c->setInstance("foo", "bar");
        $r = new Reference(["foo"]);

        $this->assertSame("bar", $r->resolve($c));
    }

    public function testNestedArrayReference()
    {
        $c = new Container();
        $c2 = new Container();

        $c2->setInstance("bar", "baz");
        $c->setInstance("foo", $c2);
        $r = new Reference(["foo", "bar"]);

        $this->assertSame("baz", $r->resolve($c));
    }
}
