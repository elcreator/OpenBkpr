<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container;

/**
 * Container args can implement this interface to dynamically resolve in interesting ways.
 */
interface ReferenceInterface
{
    /**
     * Resolve the reference.
     *
     * @param Container $container The container resolving the reference.
     * @param mixed|null $instance If the reference is being resolved on an already instantiated object it will be passed here.
     * @return mixed Returns the resolved reference.
     */
    public function resolve(Container $container, $instance = null);
}
