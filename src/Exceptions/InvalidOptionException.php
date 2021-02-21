<?php

namespace CodeDistortion\Options\Exceptions;

/**
 * Exception for when invalid options methods are used.
 */
class InvalidOptionException extends OptionsException
{
    /**
     * Return a new instance when a unexpected option was used.
     *
     * @param string $name The name of the unexpected option used.
     * @return static
     */
    public static function unexpectedOption(string $name): self
    {
        return new static('The option "' . $name . '" was not expected');
    }

    /**
     * Return a new instance when an invalid option (or it's value) was used.
     *
     * @param string $name  The name of the invalid option used.
     * @param mixed  $value The invalid value used.
     * @return static
     */
    public static function invalidOptionOrValue(string $name, $value): self
    {
        return new static('The option "' . $name . '" and/or it\'s value "' . $value . '" are not allowed');
    }
}
