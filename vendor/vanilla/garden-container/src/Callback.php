<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container;

use Prophecy\Call\Call;

/**
 * A reference that uses a callback to resolve.
 */
class Callback implements ReferenceInterface
{
    /**
     * @var callable $callback
     */
    private $callback;

    /**
     * Construct a new instance of the {@link Callback} class.
     *
     * @param callable $callback The callback of the reference.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Container $container, $instance = null)
    {
        return call_user_func($this->callback, $container, $instance);
    }

    /**
     * Get the callback.
     *
     * @return callable Returns the callback.
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * Set the callback.
     *
     * @param callable $callback The new callback to set.
     * @return Callback Returns `$this` for fluent calls.
     */
    public function setCallback(callable $callback): Callback
    {
        $this->callback = $callback;
        return $this;
    }
}
