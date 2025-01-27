<?php

declare(strict_types=1);

namespace CodeDistortion\Options\Tests\Unit;

use CodeDistortion\Options\Exceptions\InvalidOptionException;
use CodeDistortion\Options\Exceptions\OptionsException;
use CodeDistortion\Options\Tests\PHPUnitTestCase;
use CodeDistortion\Options\Tests\Unit\Support\InvokableClass;
use DateTime;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Test the Exceptions classes
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ExceptionsUnitTest extends PHPUnitTestCase
{
    /**
     * Test InvalidOptionException.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_invalid_option_exception()
    {
        self::assertInstanceOf(Exception::class, InvalidOptionException::unexpectedOption('abc'));
        self::assertInstanceOf(OptionsException::class, InvalidOptionException::unexpectedOption('abc'));

        self::assertInstanceOf(Exception::class, InvalidOptionException::invalidOptionOrValue('abc', 'def'));
        self::assertInstanceOf(OptionsException::class, InvalidOptionException::invalidOptionOrValue('abc', 'def'));



        self::assertSame(
            'The option "abc" was not expected',
            InvalidOptionException::unexpectedOption('abc')->getMessage()
        );



        self::assertSame(
            'Option arrays must contain strings, or have keys (the option names)',
            InvalidOptionException::optionArraysMustContainStringsOrHaveKeys()->getMessage()
        );



        // string
        self::assertSame(
            "The option \"abc\" and/or it's value \"xyz\" are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', 'xyz')->getMessage()
        );

        // integer
        self::assertSame(
            "The option \"abc\" and/or it's value \"123\" are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', 123)->getMessage()
        );

        // float
        self::assertSame(
            "The option \"abc\" and/or it's value \"123.456\" are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', 123.456)->getMessage()
        );

        // boolean (true)
        self::assertSame(
            "The option \"abc\" and/or it's value (true) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', true)->getMessage()
        );
        // boolean (false)
        self::assertSame(
            "The option \"abc\" and/or it's value (false) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', false)->getMessage()
        );

        // null
        self::assertSame(
            "The option \"abc\" and/or it's value (null) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', null)->getMessage()
        );

        // array
        self::assertSame(
            "The option \"abc\" and/or it's value (array) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', ['xyz'])->getMessage()
        );

        // object
        self::assertSame(
            "The option \"abc\" and/or it's value (object) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', new stdClass())->getMessage()
        );

        // object - invokeable class
        $callable = new InvokableClass();
        self::assertSame(
            "The option \"abc\" and/or it's value (object) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', $callable)->getMessage()
        );

        // callable - closure
        $callable = function () {
            return true;
        };
        self::assertSame(
            "The option \"abc\" and/or it's value (callable) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', $callable)->getMessage()
        );

        // callable - array
        $callable = [new DateTime(), 'format'];
        self::assertSame(
            "The option \"abc\" and/or it's value (callable) are not allowed",
            InvalidOptionException::invalidOptionOrValue('abc', $callable)->getMessage()
        );

        // resource
        $fileResource = fopen('php://temp', 'r');
        if ($fileResource !== false) {
            self::assertSame(
                "The option \"abc\" and/or it's value (resource) are not allowed",
                InvalidOptionException::invalidOptionOrValue('abc', $fileResource)->getMessage()
            );
            fclose($fileResource);
        }
    }
}
