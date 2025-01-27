<?php

declare(strict_types=1);

namespace CodeDistortion\Options\Tests\Unit\Support;

/**
 * An invokeable class for testing.
 */
class InvokableClass
{
    /**
     * Invoke the class.
     *
     * @return string
     */
    public function __invoke(): string
    {
        return "I'm both callable and an object!";
    }
}
