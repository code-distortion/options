<?php

namespace CodeDistortion\Options\Exceptions;

/**
 * Exception for when undefined methods are called.
 */
class UndefinedMethodException extends OptionsException
{
    use ExceptionTrait;

    /**
     * Return a new instance when a undefined method was called.
     *
     * @param string $method The method that was called.
     *
     * @return static
     */
    public static function undefinedMethod(string $method): self
    {
        return new static('Undefined method: '.static::getCallingClass().'::'.$method.'()');
    }

    /**
     * Return a new instance when a undefined static method was called.
     *
     * @param string $method The method that was called.
     *
     * @return static
     */
    public static function undefinedStaticMethod(string $method): self
    {
        return new static('Undefined static method: '.static::getCallingClass().'::'.$method.'()');
    }
}
