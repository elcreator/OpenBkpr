<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container;

/**
 * Interface representing configuration of a container.
 *
 * No methods for instantiation are provided.
 */
interface ContainerConfigurationInterface
{
    /**
     * Set the current rule.
     *
     * @param string $id The ID of the rule.
     * @return $this
     */
    public function rule(string $id);

    /**
     * Set the current rule to the default rule.
     *
     * @return $this
     */
    public function defaultRule();

    /**
     * Set the name of the class for the current rule.
     *
     * @param string $className A valid class name.
     * @return $this
     */
    public function setClass(string $className);

    /**
     * Get the rule that the current rule references.
     *
     * @return string Returns a reference name or an empty string if there is no reference.
     */
    public function getAliasOf(): string;

    /**
     * Set the rule that the current rule is an alias of.
     *
     * @param string $alias The name of an entry in the container to point to.
     * @return $this
     */
    public function setAliasOf(string $alias);

    /**
     * Get the class name of the current rule.
     *
     * @return string Returns a class name.
     */
    public function getClass(): string;

    /**
     * Add an alias of the current rule.
     *
     * Setting an alias to the current rule means that getting an item with the alias' name will be like getting the item
     * with the current rule. If the current rule is shared then the same shared instance will be returned. You can add
     * multiple aliases by passing additional arguments to this method.
     *
     * If {@link ContainerConfigurationInterface::addAlias()} is called with an alias that is the same as the current rule then an **E_USER_NOTICE**
     * level error is raised and the alias is not added.
     *
     * @param string ...$alias The alias to set.
     * @return $this
     * @since 1.4 Added the ability to pass multiple aliases.
     */
    public function addAlias(string ...$alias);
    /**
     * Remove an alias of the current rule.
     *
     * If {@link ContainerConfigurationInterface::removeAlias()} is called with an alias that references a different rule then an **E_USER_NOTICE**
     * level error is raised, but the alias is still removed.
     *
     * @param string $alias The alias to remove.
     * @return $this
     */
    public function removeAlias(string $alias);

    /**
     * Get all of the aliases of the current rule.
     *
     * This method is intended to aid in debugging and should not be used in production as it walks the entire rule array.
     *
     * @return string[] Returns an array of strings representing aliases.
     */
    public function getAliases(): array;

    /**
     * Get the factory callback for the current rule.
     *
     * @return callable|null Returns the rule's factory or **null** if it has none.
     */
    public function getFactory(): ?callable;

    /**
     * Set the factory that will be used to create the instance for the current rule.
     *
     * @param callable|null $factory This callback will be called to create the instance for the rule.
     * @return $this
     */
    public function setFactory(?callable $factory = null);

    /**
     * Whether or not the current rule is shared.
     *
     * @return bool Returns **true** if the rule is shared or **false** otherwise.
     */
    public function isShared(): bool;

    /**
     * Set whether or not the current rule is shared.
     *
     * @param bool $shared Whether or not the current rule is shared.
     * @return $this
     */
    public function setShared(bool $shared);

    /**
     * Whether or not the current rule will inherit to subclasses.
     *
     * @return bool Returns **true** if the current rule inherits or **false** otherwise.
     */
    public function getInherit(): bool;

    /**
     * Set whether or not the current rule extends to subclasses.
     *
     * @param bool $inherit Pass **true** to have subclasses inherit this rule or **false** otherwise.
     * @return $this
     */
    public function setInherit(bool $inherit);

    /**
     * Get the constructor arguments for the current rule.
     *
     * @return array Returns the constructor arguments for the current rule.
     */
    public function getConstructorArgs(): array;

    /**
     * Set the constructor arguments for the current rule.
     *
     * @param array $args An array of constructor arguments.
     * @return $this
     */
    public function setConstructorArgs(array $args);

    /**
     * Add a method call to a rule.
     *
     * @param string $method The name of the method to call.
     * @param array $args The arguments to pass to the method.
     * @return $this
     */
    public function addCall(string $method, array $args = []);

    /**
     * Determines whether a rule has been defined at a given ID.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool Returns **true** if a rule has been defined or **false** otherwise.
     */
    public function hasRule(string $id): bool;
}
