<?php

declare(strict_types=1);

namespace CodeDistortion\Options\Tests\Unit;

use CodeDistortion\Options\Exceptions\InvalidOptionException;
use CodeDistortion\Options\Options;
use CodeDistortion\Options\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Test the Options library.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class OptionsUnitTest extends PHPUnitTestCase
{
    /**
     * Test the new() alternative constructor.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_the_new_alternative_constructor()
    {
        $options = Options::new('a');
        self::assertInstanceOf(Options::class, $options);
        self::assertSame(['a' => true], $options->all());

        // test that the new() method will generate a brand-new Options object
        $options->defaults('b');
        self::assertSame(['a' => true, 'b' => true], $options->all());

        $options2 = $options->new('a');
        self::assertNotSame($options, $options2);
        self::assertSame(['a' => true], $options2->all());
    }





    /**
     * Test the resolving of options (i.e. the options() and amendOptions() methods).
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_resolving_options()
    {
        $validatorLower = function (string $name, $value, bool $wasExpected) {
            return $name === mb_strtolower($name);
        };



        // test that amendOptions() can be called to add to the options, even if there were none to begin with
        $options = new Options();
        $options->amendOptions(['d']);
        self::assertSame(['d' => true], $options->all());

        // test that calling options() again will replace the previous options
        $options->options(['a', '!c']);
        self::assertSame(['a' => true, 'c' => false], $options->all());

        // test that amendOptions() can be called to add to the options
        $options->amendOptions(['d']);
        self::assertSame(['a' => true, 'c' => false, 'd' => true], $options->all());



        // test that new custom values can be resolved, overriding the ones previously passed
        $options = Options::new('a');
        $options->options('b');
        self::assertSame(['b' => true], $options->all());

        // check that calling the method with no arguments resets the custom values
        $options->options();
        self::assertSame([], $options->all());



        // check that it doesn't reset any other settings

        // validator
        $options->validator($validatorLower);
        $options->options(['a', 'INVALID']);
        self::assertSame(['a' => true], $options->all());

        // defaults
        $options->defaults('z');
        $options->options('a');
        self::assertSame(['a' => true, 'z' => true], $options->all());

        // restrictUnexpected
        $options->restrictUnexpected(true, true);
        $options->defaults('z');
        $options->options('a');
        $caughtException = false;
        try {
            $options->all();
        } catch (InvalidOptionException $throwable) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);



        // test that it doesn't matter which order resolve() is called in, in relation to the rest
        $options = new Options();
        $options->options(['!a', 'b', 'C']);
        $options->defaults('a');
        $options->restrictUnexpected();
        $options->validator($validatorLower);
        self::assertSame(['a' => false], $options->all());

        $options = new Options();
        $options->defaults('a');
        $options->restrictUnexpected();
        $options->validator($validatorLower);
        $options->options(['!a', 'b', 'C']);
        self::assertSame(['a' => false], $options->all());
    }





    /**
     * Test defaults (i.e. the defaults() and amendDefaults() methods).
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_defaults()
    {
        // test that there are no defaults to begin with
        $options = new Options();
        self::assertSame([], $options->all());



        // test that amendDefaults() can be called to add to the defaults, even if there were none to begin with
        $options = new Options();
        $options->amendDefaults(['d']);
        self::assertSame(['d' => true], $options->all());

        // test that calling defaults() again will replace the previous defaults
        $options->defaults(['a', '!c']);
        self::assertSame(['a' => true, 'c' => false], $options->all());

        // test that amendDefaults() can be called to add to the defaults
        $options->amendDefaults(['d']);
        self::assertSame(['a' => true, 'c' => false, 'd' => true], $options->all());

        // test that defaults can be overridden by custom values
        $options->options(['!a', 'd']);
        self::assertSame(['a' => false, 'c' => false, 'd' => true], $options->all());

        // test that resetting the default options won't affect the custom values
        $options->defaults();
        self::assertSame(['a' => false, 'd' => true], $options->all());



        // test that it doesn't matter which order defaults() + amendDefaults() are called in, in relation to the rest
        $validatorLower = function (string $name, $value, bool $wasExpected) {
            return $name === mb_strtolower($name);
        };

        $options = new Options();
        $options->defaults(['A', '!b']);
        $options->amendDefaults(['c']);
        $options->options(['!A', 'd']);
        $options->restrictUnexpected();
        $options->validator($validatorLower);
        self::assertSame(['b' => false, 'c' => true], $options->all());

        $options = new Options();
        $options->options(['!A', 'd']);
        $options->restrictUnexpected();
        $options->validator($validatorLower);
        $options->defaults(['A', '!b']);
        $options->amendDefaults(['c']);
        self::assertSame(['b' => false, 'c' => true], $options->all());
    }





    /**
     * Test the restriction of unexpected options (i.e. the restrictUnexpected() method).
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_restrict_unexpected()
    {
        // test that the custom options aren't restricted by default
        $options = new Options();
        $options->options(['a', 'b']);
        self::assertSame(['a' => true, 'b' => true], $options->all());

        // test that restrictUnexpected() can be called to restrict unexpected options
        $options->restrictUnexpected();
        self::assertSame([], $options->all());

        // test that restrictUnexpected(false) can be called to allow unexpected options again
        $options->restrictUnexpected(false);
        self::assertSame(['a' => true, 'b' => true], $options->all());

        // test that restrictUnexpected(true) can be called to restrict unexpected options again
        $options->restrictUnexpected(true);
        self::assertSame([], $options->all());

        // test that default options cause custom options to become expected
        $options->defaults(['a']);
        self::assertSame(['a' => true], $options->all());

        $options->defaults(['a', 'b', 'c']);
        self::assertSame(['a' => true, 'b' => true, 'c' => true], $options->all());



        // test that no exception is thrown when no unexpected options are detected
        $options = new Options();
        $options->restrictUnexpected(true, true);
        $options->options();
        self::assertSame([], $options->all());

        // test that an exception is thrown when an unexpected option is detected
        $options->options(['a', 'b']);
        $caughtException = false;
        try {
            $options->all();
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);



        // test that restricting unknown options is applied before the validator checks
        $validatorLower = function (string $name, $value, bool $wasExpected) {
            return $name === mb_strtolower($name);
        };

        $options = new Options();
        $options->defaults(['a', 'B']);
        $options->options(['a', 'B', 'c']);
        $options->validator($validatorLower);
        $options->restrictUnexpected();
        self::assertSame(['a' => true], $options->all());



        // test that it doesn't matter which order restrictUnexpected() is called in, in relation to the rest
        $options = new Options();
        $options->restrictUnexpected();
        $options->defaults(['a', 'B']);
        $options->options(['a', 'B', 'c']);
        $options->validator($validatorLower);
        self::assertSame(['a' => true], $options->all());



        // test that options after an ignored option are still processed
        $options = new Options();
        $options->defaults(['z-expected']);
        $options->options(['unexpected', 'z-expected']);
        $options->restrictUnexpected();
        self::assertSame(['z-expected' => true], $options->all());
    }





    /**
     * Test validation of options (i.e. the validator() method).
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_validation()
    {
        $validatorLower = function (string $name, $value, bool $wasExpected) {
            return $name === mb_strtolower($name);
        };
        $validatorUpper = function (string $name, $value, bool $wasExpected) {
            return $name === mb_strtoupper($name);
        };



        // test that the validator can be set
        $options = Options::new()->validator($validatorLower)->options(['lower', 'UPPER']);
        self::assertSame(['lower' => true], $options->all());

        // test that calling validator() again will replace the previous validator
        $options->validator($validatorUpper);
        self::assertSame(['UPPER' => true], $options->all());

        // test that default values are validated as well
        $options->defaults(['lower-default', 'UPPER-DEFAULT']);
        self::assertSame(['UPPER' => true, 'UPPER-DEFAULT' => true], $options->all());



        // test that no exception is thrown when no invalid options are detected
        $options = new Options();
        $options->validator($validatorLower, true);
        $options->options(['lower']);
        self::assertSame(['lower' => true], $options->all());

        // test that an exception is thrown when an invalid option is detected
        $options->options(['UPPER']);
        $caughtException = false;
        try {
            $options->all();
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        // test that default values are validated as well, and that an exception is thrown when detected
        $options->options();
        $options->defaults(['UPPER-DEFAULT']);
        $caughtException = false;
        try {
            $options->all();
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);



        // test that it operates without a validator
        $options = new Options();
        $options->options(['lower', 'UPPER']);
        self::assertSame(['UPPER' => true, 'lower' => true, ], $options->all());

        // test that the validator can be removed again by passing null
        $options->validator($validatorLower);
        self::assertSame(['lower' => true], $options->all());
        $options->validator(null);
        self::assertSame(['UPPER' => true, 'lower' => true], $options->all());



        // test that it doesn't matter which order validator() is called in, in relation to the rest
        $options = new Options();
        $options->validator($validatorLower);
        $options->defaults(['lower-default', 'UPPER-DEFAULT']);
        $options->options(['lower', 'UPPER']);
        $options->restrictUnexpected();
        self::assertSame(['lower-default' => true], $options->all());

        $options = new Options();
        $options->defaults(['lower-default', 'UPPER-DEFAULT']);
        $options->options(['lower', 'UPPER']);
        $options->restrictUnexpected();
        $options->validator($validatorLower);
        self::assertSame(['lower-default' => true], $options->all());



        // test validation with a different signatures
        $validator = function (bool $wasExpected, string $name, $value) {
            return $name === mb_strtolower($name);
        };
        $options = new Options();
        $options->validator($validator);
        $options->options(['lower', 'UPPER']);
        self::assertSame(['lower' => true], $options->all());

        $validator = function ($name) {
            return is_string($name) && ($name === mb_strtolower($name));
        };
        $options = new Options();
        $options->validator($validator);
        $options->options(['lower', 'UPPER']);
        self::assertSame(['lower' => true], $options->all());

        // test validation with a validator that accepts no parameters
        $validator = function () {
            return true;
        };
        $options = new Options();
        $options->validator($validator);
        $options->options(['lower', 'UPPER']);
        self::assertSame(['UPPER' => true, 'lower' => true], $options->all());



        // test validation that returns truthy
        $validator0 = function (string $name, $value, bool $wasExpected) {
            return 0;
        };
        $options = new Options();
        $options->validator($validator0);
        $options->options(['lower', 'UPPER']);
        self::assertSame([], $options->all());

        // test validation that returns falsey
        $validator1 = function (string $name, $value, bool $wasExpected) {
            return 1;
        };
        $options = new Options();
        $options->validator($validator1);
        $options->options(['lower', 'UPPER']);
        self::assertSame(['UPPER' => true, 'lower' => true], $options->all());



        // test that default values are "expected"
        $validator = function (string $name, $value, bool $wasExpected) {
            return $wasExpected;
        };
        $options = new Options();
        $options->defaults(['expected']);
        $options->validator($validator);
        $options->options(['unexpected']);
        self::assertSame(['expected' => true], $options->all());
    }





    /**
     * Test that retrieving values twice doesn't cause any problems.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_that_caching_of_resolved_values_works()
    {
        $options = new Options();
        $options->options(['a', 'b']);
        self::assertSame(['a' => true, 'b' => true], $options->all());
        self::assertSame(['a' => true, 'b' => true], $options->all());
    }





    /**
     * Test that different types (i.e. structures: strings, arrays, multiple arguments) of input can be processed.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_the_different_types_of_input()
    {
        $stdClass = new stdClass();

        // pass a string
        self::assertSame(['a' => true], Options::new('a')->all());



        // pass an array of strings
        self::assertSame(['a' => true, 'b' => true], Options::new(['a', 'b'])->all());



        // pass an array of key-value pairs with different types of values
        self::assertSame(['a' => true], Options::new(['a' => true])->all());
        self::assertSame(['a' => 1], Options::new(['a' => 1])->all());
        self::assertSame(['a' => 1.1], Options::new(['a' => 1.1])->all());
        self::assertSame(['a' => 'b'], Options::new(['a' => 'b'])->all());
        self::assertSame(['a' => ['b']], Options::new(['a' => ['b']])->all());
        self::assertSame(['a' => $stdClass], Options::new(['a' => $stdClass])->all());



        // pass an integer indexed array with different types of values
        self::assertSame(['a' => true, 'b' => true], Options::new(['a', 'b'])->all());

        // pass an integer indexed array with non-string values
        // boolean value/s
        $caughtException = false;
        try {
            Options::new([true]);
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        // integer value/s
        $caughtException = false;
        try {
            Options::new([1]);
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        // float value/s
        $caughtException = false;
        try {
            Options::new([1.1]);
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        // array value/s
        $caughtException = false;
        try {
            Options::new([['a']]);
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);

        // object value/s
        $caughtException = false;
        try {
            Options::new([$stdClass]);
        } catch (InvalidOptionException $e) {
            $caughtException = true;
        }
        self::assertTrue($caughtException);



        // pass an array with mixed keys
        self::assertSame(['a' => true, 'b' => 'c'], Options::new(['a', 'b' => 'c'])->all());



        // pass multiple arguments
        self::assertSame(['a' => true, 'b' => true], Options::new('a', 'b')->all());



        // pass multiple arguments with values that overlap
        self::assertSame(['a' => false], Options::new(['a'], ['!a'])->all());
        self::assertSame(['a' => 'test'], Options::new(['a' => true], ['a' => 'test'])->all());
    }





    /**
     * Test the retrieval of option values.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_the_retrieval_of_option_values()
    {
        // retrieve when no options have been set
        $options = new Options();
        self::assertSame([], $options->all());

        $options = new Options();
        self::assertFalse($options->has('a'));
        self::assertFalse($options->has('c'));

        $options = new Options();
        self::assertNull($options->get('a'));
        self::assertNull($options->get('c'));

        // retrieve when options have been set
        $options = new Options(['a', 'b']);
        self::assertSame(['a' => true, 'b' => true], $options->all());

        $options = new Options(['a', 'b']);
        self::assertTrue($options->has('a'));
        self::assertFalse($options->has('c'));

        $options = new Options(['a', 'b']);
        self::assertTrue($options->get('a'));
        self::assertNull($options->get('c'));
    }





    /**
     * Test that the class detects different variations of options properly.
     *
     * @test
     * @dataProvider optionDataProvider
     *
     * @param array<integer,mixed> $inputArgs       The input to resolve.
     * @param array<string,mixed>  $expectedOutcome The output expected from parsing.
     * @return void
     */
    #[Test]
    #[DataProvider('optionDataProvider')]
    public function test_detection_of_options(array $inputArgs, array $expectedOutcome)
    {
        $options = new Options();
        call_user_func_array([$options, 'options'], $inputArgs);

        self::assertSame($expectedOutcome, $options->all());
    }

    /**
     * Provide the data for the test_option_detection test below.
     *
     * @return array<integer, array<string, mixed>>
     */
    public static function optionDataProvider(): array
    {
        // all the different inputs that could be passed
        $allVariations = [

            [null,             []],

            // strings
            ['',               []],
            ['a',              ['a' => true]],
            ['+a',             ['a' => true]],
            ['-a',             ['a' => false]],
            ['!a',             ['a' => false]],

            ['a=',             ['a' => '']],
            ['a=z',            ['a' => 'z']],
            ['a=true',         ['a' => true]],
            ['a=false',        ['a' => false]],
            ['a=null',         ['a' => null]],
            ['a=100',          ['a' => 100]],
            ['a=a100',         ['a' => 'a100']],
            ['a=100z',         ['a' => '100z']],
            ['a=-999',         ['a' => -999]],
            ['a=123.456',      ['a' => 123.456]],
            ['a=a123.456',     ['a' => 'a123.456']],
            ['a=123.456z',     ['a' => '123.456z']],
            ['a=-999.333',     ['a' => -999.333]],
            ['"a a"=a',        ['a a' => 'a']],

            ['a=""',           ['a' => '']],
            ['a="z"',          ['a' => 'z']],
            ['a="true"',       ['a' => 'true']],
            ['a="false"',      ['a' => 'false']],
            ['a="null"',       ['a' => 'null']],
            ['a="\'"',         ['a' => '\'']],
            ['"a"="a"',        ['a' => 'a']],
            ['"a a"="a"',      ['a a' => 'a']],
            ['a\\"',           ['a\\"' => true]],
            ['a=b\\"',         ['a' => 'b\\"']],

            ['a=\'\'',         ['a' => '']],
            ['a=\'z\'',        ['a' => 'z']],
            ['a=\'true\'',     ['a' => 'true']],
            ['a=\'false\'',    ['a' => 'false']],
            ['a=\'null\'',     ['a' => 'null']],
            ['a=\'"\'',        ['a' => '"']],
            ['\'a\'=\'a\'',    ['a' => 'a']],
            ['\'a a\'=\'a\'',  ['a a' => 'a']],
            ['a\\\'',          ['a\\\'' => true]],
            ['a=b\\\'',        ['a' => 'b\\\'']],

            // arrays of strings
            [[''],             []],
            [['a'],            ['a' => true]],
            [['+a'],           ['a' => true]],
            [['-a'],           ['a' => false]],
            [['!a'],           ['a' => false]],

            [['a='],           ['a' => '']],
            [['a=z'],          ['a' => 'z']],
            [['a=true'],       ['a' => true]],
            [['a=false'],      ['a' => false]],
            [['a=null'],       ['a' => null]],

            [['a=""'],         ['a' => '']],
            [['a="z"'],        ['a' => 'z']],
            [['a="true"'],     ['a' => 'true']],
            [['a="false"'],    ['a' => 'false']],
            [['a="null"'],     ['a' => 'null']],
            [['a="\'"'],       ['a' => '\'']],

            [['a=\'\''],       ['a' => '']],
            [['a=\'z\''],      ['a' => 'z']],
            [['a=\'true\''],   ['a' => 'true']],
            [['a=\'false\''],  ['a' => 'false']],
            [['a=\'null\''],   ['a' => 'null']],
            [['a=\'"\''],      ['a' => '"']],

            // arrays - key value pairs
            [[],               []],
            [['' => ''],       []],
            [['' => true],     []],
            [['' => 'abc'],    []],
            [[' '],            []],
            [[' ' => true],    []],
            [[' ' => 'abc'],   []],
            [['a' => null],    ['a' => null]],
            [['a' => true],    ['a' => true]],
            [['a' => false],   ['a' => false]],
            [['a' => 'z'],     ['a' => 'z']],
        ];

        // some explicit combinations put together manually
        $manualCombinations = [
            ['-a a a=null a="true" a=\'blah\'', ['a' => 'blah']],
            ['a=test b', ['a' => 'test', 'b' => true]],
            [' a="null"b ', ['a' => 'null', 'b' => true]],
            ['a=1,b', ['a' => 1, 'b' => true]],
            ['a=-1,b', ['a' => -1, 'b' => true]],
            ['a=a,b=b', ['a' => 'a', 'b' => 'b']],
            ['"hello there",\'this is a test\'="ok"', ['hello there' => true, 'this is a test' => 'ok']],
            ['&&&&&&&a=b', ['&&&&&&&a' => 'b']],
            ['!@#$%^&*()=abc', ['!@#$%^&*()' => 'abc']],
        ];

        // different inputs that will be combined with others to make sure they're interpreted properly
        $combinationVariations = [

            // strings
            ['a',              ['a' => true]],
            ['-b',             ['b' => false]],

            ['c=z',            ['c' => 'z']],

            ['d="z"',          ['d' => 'z']],

            ['e=\'z\'',        ['e' => 'z']],

            // arrays of strings
            [['f'],            ['f' => true]],

            // arrays - key value pairs
            [['g' => true],    ['g' => true]],
            [['h' => 'z'],     ['h' => 'z']],
        ];

        $data = [];
        $data = array_merge($data, self::buildCombinations($allVariations, 1));
        $data = array_merge($data, self::buildCombinations($manualCombinations, 1));
        $data = array_merge($data, self::buildCombinations($combinationVariations, 4));

        return $data;
    }

    /**
     * Take the input variations and piece together the possible different variations.
     *
     * @param array<integer, mixed[]> $variations   Array containing the input and output values.
     * @param integer                 $recurseCount The number of times to recurse (how many inputs per combination?).
     * @return array<integer, array<string, mixed>>
     */
    protected static function buildCombinations(array $variations, int $recurseCount = 1): array
    {
        // build the input combinations and their expected outcomes
        $recurse = function (int $depthLeft) use (&$recurse, $variations) {

            $returnInputs = $returnOutputs = [];
            if ($depthLeft > 1) {

                $combinations = $recurse($depthLeft - 1);
                $existingInputs = $combinations[0];
                $existingOutputs = $combinations[1];

                foreach (array_keys($existingInputs) as $existingIndex) {

                    foreach (array_keys($variations) as $variationIndex) {

                        $input = $variations[$variationIndex][0];
                        $output = $variations[$variationIndex][1];

                        if (is_array($output)) { // for PHPStan
                            $returnInputs[] = array_merge($existingInputs[$existingIndex], [$input]);
                            $returnOutputs[] = array_merge($existingOutputs[$existingIndex], $output);
                        }
                    }
                }
            } else {
                foreach (array_keys($variations) as $variationIndex) {

                    $input = $variations[$variationIndex][0];
                    $output = $variations[$variationIndex][1];

                    $returnInputs[][] = $input;
                    $returnOutputs[] = $output;
                }
            }
            return [$returnInputs, $returnOutputs];
        };
        $combinations = $recurse($recurseCount);
        $inputs = $combinations[0];
        $outputs = $combinations[1];



        // stick the inputs together a bit better
        $combinedInputs = [];
        foreach (array_keys($inputs) as $index) {

            $currentString = '';
            $currentArray = [];
            $currentInputs = [];
            foreach ($inputs[$index] as $input) {

                // stick strings together
                if (is_string($input)) {
                    if (count($currentArray) > 0) {
                        $currentInputs[] = $currentArray;
                        $currentArray = [];
                    }
                    $currentString .= ((mb_strlen($currentString) > 0) ? ' ' : '') . $input;
                // stick arrays together
                } elseif (is_array($input)) {
                    if (mb_strlen($currentString) > 0) {
                        $currentInputs[] = $currentString;
                        $currentString = '';
                    }

                    // don't use the value if it has been added earlier (earlier values have higher precedence)
                    $foundKey = false;
                    foreach (array_keys($input) as $key) {
                        if (!is_int($key)) {
                            if (array_key_exists($key, $currentArray)) {
                                $foundKey = true;
                            }
                        }
                    }
                    if (!$foundKey) {
                        $currentArray = array_merge($currentArray, $input);
                    }
                } else {
                    $currentArray = array_merge($currentArray, [$input]);
                }
            }
            if (count($currentArray) > 0) {
                $currentInputs[] = $currentArray;
            } elseif (mb_strlen($currentString) > 0) {
                $currentInputs[] = $currentString;
            }

            $combinedInputs[$index] = $currentInputs;
        }



        // pair the inputs and their expected outputs together and return them
        $data = [];
        foreach (array_keys($combinedInputs) as $index) {
            ksort($outputs[$index]);
            $data[] = ['inputArgs' => $combinedInputs[$index], 'expectedOutcome' => $outputs[$index]];
        }

        return $data;
    }





    /**
     * Test that the class detects different variations of options properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_detection_of_options2()
    {
        // the various ways of specifying the keys and their values
        $options = new Options(
            'a b= c=12 c2=12.3 d="12" d2="\"12" e=\'999\' e2=\'\\\'999\' "hi \"the"=xyz1 \'hallo \\\'!\'=xyz2'
        );

        self::assertSame(
            [
                'a' => true,
                'b' => '',
                'c' => 12,
                'c2' => 12.3,
                'd' => '12',
                'd2' => '"12',
                'e' => '999',
                'e2' => '\'999',
                'hallo \'!' => 'xyz2',
                'hi "the' => 'xyz1',
            ],
            $options->all()
        );



        // test that options are ordered alphabetically
        $options = new Options('b c a');
        self::assertSame(['a' => true, 'b' => true, 'c' => true], $options->all());
    }
}
