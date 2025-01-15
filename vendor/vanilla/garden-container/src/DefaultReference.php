<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 * @internal
 */

namespace Garden\Container;

/**
 * Used internally.
 */
class DefaultReference implements ReferenceInterface
{
    /**
     * @var string
     */
    protected $class;

    /**
     * Construct a new instance of the {@link Reference} class.
     *
     * @param string $class The name of the reference.
     */
    public function __construct(string $class)
    {
        $this->setClass($class);
    }

    /**
     * Get the name of the reference.
     *
     * @return string Returns the name of the reference.
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set the name of the reference.
     *
     * @param string $class The name of the reference.
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Container $container, $instance = null)
    {
        return $container->get($this->class);
    }
}
