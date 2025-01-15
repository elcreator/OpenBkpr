<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Container;

/**
 * An exception that is thrown when a function/method is called with a missing parameter.
 */
class MissingArgumentException extends ContainerException
{
    private $arg;
    private $function;

    /**
     * MissingArgumentException constructor.
     *
     * @param string $arg The name of the missing parameter.
     * @param string $function The name of the function being called, but can be empty when not known.
     */
    public function __construct($arg, $function = "")
    {
        $this->arg = $arg;
        $this->function = $function;

        if (empty($function)) {
            $message = sprintf('Missing argument $%s.', $arg);
        } else {
            $message = sprintf('Missing argument $%s for %s.', $arg, $function);
        }

        parent::__construct($message, 500);
    }
}
