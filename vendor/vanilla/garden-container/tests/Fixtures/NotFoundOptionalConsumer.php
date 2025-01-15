<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container\Tests\Fixtures;

class NotFoundOptionalConsumer
{
    public $foo;
    public $configValue;

    public function __construct(SomeNonExistantInterface $foo = null, $configValue = false)
    {
        $this->foo = $foo;
        $this->configValue = $configValue;
    }
}
