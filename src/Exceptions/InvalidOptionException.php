<?php

declare(strict_types=1);

namespace CodeDistortion\Options\Exceptions;

use Closure;

/**
 * Exception for when invalid options methods are used.
 */
class InvalidOptionException extends OptionsException
{
    /**
     * When an unexpected option was used.
     *
     * @param string $name The name of the unexpected option used.
     * @return self
     */
    public static function unexpectedOption(string $name): self
    {
        return new self("The option \"$name\" was not expected");
    }

    /**
     * When an option array is passed without keys.
     *
     * @return self
     */
    public static function optionArraysMustContainStringsOrHaveKeys(): self
    {
        return new self('Option arrays must contain strings, or have keys (the option names)');
    }

    /**
     * When an invalid option (or it's value) was used.
     *
     * @param string $name  The name of the invalid option used.
     * @param mixed  $value The invalid value used.
     * @return self
     */
    public static function invalidOptionOrValue(string $name, $value): self
    {
        if (is_string($value)) {
            return new self("The option \"$name\" and/or it's value \"$value\" are not allowed");
        }
        if (is_int($value)) {
            return new self("The option \"$name\" and/or it's value \"$value\" are not allowed");
        }
        if (is_float($value)) {
            return new self("The option \"$name\" and/or it's value \"$value\" are not allowed");
        }
        if (is_bool($value)) {
            $value = $value
                ? 'true'
                : 'false';
            return new self("The option \"$name\" and/or it's value ($value) are not allowed");
        }
        if (is_null($value)) {
            return new self("The option \"$name\" and/or it's value (null) are not allowed");
        }
        if ($value instanceof Closure) {
            return new self("The option \"$name\" and/or it's value (callable) are not allowed");
        }
        if (is_object($value)) {
            return new self("The option \"$name\" and/or it's value (object) are not allowed");
        }
        if (is_callable($value)) {
            return new self("The option \"$name\" and/or it's value (callable) are not allowed");
        }
        if (is_array($value)) {
            return new self("The option \"$name\" and/or it's value (array) are not allowed");
        }
        if (is_resource($value)) {
            return new self("The option \"$name\" and/or it's value (resource) are not allowed");
        }
        return new self("The option \"$name\" and/or it's value are not allowed");
    }
}
