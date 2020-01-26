<?php

namespace CodeDistortion\Options\Tests\Unit;

use CodeDistortion\Options\Tests\TestCase;
use CodeDistortion\Options\Options;
use CodeDistortion\Options\Exceptions\InvalidOptionException;

/**
 * Test the Options library.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class OptionsUnitTest extends TestCase
{
    /**
     * Provide the data for the test_option_detection test below.
     *
     * @return array
     */
    public function optionDataProvider()
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
            ['a=-999',         ['a' => -999]],
            ['a=123.456',      ['a' => 123.456]],
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

            ['a=\'\'',         ['a' => '']],
            ['a=\'z\'',        ['a' => 'z']],
            ['a=\'true\'',     ['a' => 'true']],
            ['a=\'false\'',    ['a' => 'false']],
            ['a=\'null\'',     ['a' => 'null']],
            ['a=\'"\'',        ['a' => '"']],
            ['\'a\'=\'a\'',    ['a' => 'a']],
            ['\'a a\'=\'a\'',  ['a a' => 'a']],

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
        $data = array_merge($data, $this->buildCombinations($allVariations, 1));
        $data = array_merge($data, $this->buildCombinations($manualCombinations, 1));
        $data = array_merge($data, $this->buildCombinations($combinationVariations, 4));
        return $data;
    }

    /**
     * Take the input variations and piece together the possible different variations.
     *
     * @param array   $variations   Array containing the input and output values.
     * @param integer $recurseCount The number of times to recurse (how many inputs per combination?).
     * @return array
     */
    protected function buildCombinations(array $variations, int $recurseCount = 1): array
    {
        // build the input combinations and their expected outcomes
        $recurse = function (int $depthLeft) use (&$recurse, $variations) {

            $returnInputs = $returnOutputs = [];
            if ($depthLeft > 1) {

                [$existingInputs, $existingOutputs] = $recurse($depthLeft - 1);
                foreach (array_keys($existingInputs) as $existingIndex) {

                    foreach (array_keys($variations) as $variationIndex) {

                        $input = $variations[$variationIndex][0];
                        $output = $variations[$variationIndex][1];

                        $returnInputs[] = array_merge($existingInputs[$existingIndex], [$input]);
                        $returnOutputs[] = array_merge($existingOutputs[$existingIndex], $output);
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
        [$inputs, $outputs] = $recurse($recurseCount);



        // stick the inputs together a bit better
        $combinedInputs = [];
        foreach (array_keys($inputs) as $index) {

            $currentString = '';
            $currentArray = [];
            $currentInputs = [];
            foreach ($inputs[$index] as $input) {

                // stick strings together
                if (is_string($input)) {
                    if (count($currentArray)) {
                        $currentInputs[] = $currentArray;
                        $currentArray = [];
                    }
                    $currentString .= (mb_strlen($currentString) ? ' ' : '').$input;
                // stick arrays together
                } elseif (is_array($input)) {
                    if (mb_strlen($currentString)) {
                        $currentInputs[] = $currentString;
                        $currentString = '';
                    }

                    // don't use the value if it has been added earlier (earlien values have higher precedence0
                    $foundKey = false;
                    foreach (array_keys($input) as $key) {
                        if (!is_int($key)) {
                            $foundKey &= array_key_exists($key, $currentArray);
                        }
                    }
                    if (!$foundKey) {
                        $currentArray = array_merge($currentArray, $input);
                    }
                }
            }
            if (count($currentArray)) {
                $currentInputs[] = $currentArray;
            } elseif (mb_strlen($currentString)) {
                $currentInputs[] = $currentString;
            }

            $combinedInputs[$index] = $currentInputs;
        }


        // pair the inputs and their expected outputs together and return them
        $data = [];
        foreach (array_keys($combinedInputs) as $index) {
            $data[] = [$combinedInputs[$index], $outputs[$index]];
        }

        return $data;
    }





    /**
     * Test that the class detects different variations of options properly.
     *
     * @test
     * @dataProvider optionDataProvider
     * @param array $inputArgs       The input to parse.
     * @param array $expectedOutcome The output expected from parsing.
     * @return void
     */
    public function test_option_detection(array $inputArgs, array $expectedOutcome): void
    {
        $toCall = [Options::class, 'parse'];
        if (is_callable($toCall)) {
            $this->assertSame($expectedOutcome, call_user_func_array($toCall, $inputArgs));
        }
    }





    /**
     * Test the ways the Options class can be used.
     *
     * @test
     * @return void
     */
    public function test_usage(): void
    {
        // the various ways of specifying the keys and their values
        $this->assertSame(
            [
                'a' => true,
                'b' => '',
                'c' => 12,
                'c2' => 12.3,
                'd' => '12',
                'd2' => '"12',
                'e' => '999',
                'e2' => '\'999',
                'hi "the' => 'xyz1',
                'hallo \'!' => 'xyz2',
            ],
            Options::parse(
                'a b= c=12 c2=12.3 d="12" d2="\"12" e=\'999\' e2=\'\\\'999\' "hi \"the"=xyz1 \'hallo \\\'!\'=xyz2'
            )
        );



        // setting default options
        $this->assertSame(
            ['a' => 'a', 'b' => 'b'],
            Options::defaults(['a' => 'a', 'b' => 'b'])->getDefaults()
        );
        // add extra defaults (ie. adding new default options)
        $this->assertSame(
            ['a' => 'a', 'b' => 'B', 'c' => 'C'],
            Options::defaults(['a' => 'a', 'b' => 'b'])->addDefaults(['b' => 'B', 'c' => 'C'])->getDefaults()
        );
        // add extra with no defaults to begin with
        $this->assertSame(
            ['a' => 'A', 'b' => 'B'],
            Options::addDefaults(['a' => 'A', 'b' => 'B'])->getDefaults()
        );
        // add extra defaults, and then replace with new defaults
        $this->assertSame(
            ['b' => 'B'],
            Options::defaults(['a' => 'a', 'b' => 'b'])->addDefaults(['a' => 'A'])->defaults('b=B')->getDefaults()
        );



        // allow unexpected option 'b' that wasn't in the defaults
        $this->assertSame(['a' => true, 'b' => true], Options::allowUnexpected()->defaults('a')->resolve('b')->all());

        // allow unexpected option 'b' because there were no defaults
        $this->assertSame(['b' => true], Options::allowUnexpected()->resolve('b')->all());
        $this->assertSame(['b' => true], Options::resolve('b')->all());

        // option b was not a default so it was unexpected
        $this->assertThrows(InvalidOptionException::class, function () {
            Options::defaults('a')->resolve('b')->all();
        });



        // combine()
        $this->assertSame(
            ['a' => 'A', 'b' => 'b'],
            Options::defaults(['a' => 'a', 'b' => 'b'])->resolve(['a' => 'A'])->all() // will be combined with defaults
        );

        // parse()
        $this->assertSame(
            ['c' => 'C'],
            Options::defaults(['a' => 'a', 'b' => 'b'])->parse(['c' => 'C']) // ignores the defaults
        );



        // test how custom values are stored internally
        // and the resolved result is re-determined when certain changes occur
        $options = Options::defaults('a b c')->allowUnexpected()->resolve('-a -b -d');
        $this->assertFalse($options->get('a'));
        $this->assertTrue($options->getDefault('a'));
        $this->assertFalse($options->getCustom('a'));
        $this->assertFalse($options->get('b'));
        $this->assertTrue($options->getDefault('b'));
        $this->assertFalse($options->getCustom('b'));
        $this->assertTrue($options->get('c'));
        $this->assertTrue($options->getDefault('c'));
        $this->assertNull($options->getCustom('c'));
        $this->assertFalse($options->get('d'));
        $this->assertNull($options->getDefault('d'));
        $this->assertFalse($options->getCustom('d'));
        $this->assertNull($options->get('e'));
        $this->assertNull($options->getDefault('e'));
        $this->assertNull($options->getCustom('e'));

        $this->assertTrue($options->has('a'));
        $this->assertFalse($options->has('e'));

        $options->defaults('e'); // forces a re-resolve
        $this->assertFalse($options->get('a'));
        $this->assertFalse($options->get('b'));
        $this->assertNull($options->get('c'));
        $this->assertFalse($options->get('d'));
        $this->assertTrue($options->get('e'));

        $this->assertTrue($options->has('a'));
        $this->assertTrue($options->has('e'));

        $options->addDefaults('c'); // forces a re-resolve
        $this->assertFalse($options->get('a'));
        $this->assertFalse($options->get('b'));
        $this->assertTrue($options->get('c'));
        $this->assertFalse($options->get('d'));
        $this->assertTrue($options->get('e'));

        $this->assertTrue($options->has('a'));
        $this->assertTrue($options->has('e'));

        $options->resolve(); // forces a re-resolve
        $this->assertNull($options->get('a'));
        $this->assertNull($options->get('b'));
        $this->assertTrue($options->get('c'));
        $this->assertNull($options->get('d'));
        $this->assertTrue($options->get('e'));

        $this->assertFalse($options->has('a'));
        $this->assertTrue($options->has('e'));



        // validation of the option values when using combine()
        $params = [];
        $callback = function (string $name, $value, ?bool $wasExpected) use (&$params): bool {
            $params[] = ['name' => $name, 'value' => $value, 'wasExpected' => $wasExpected];
            return true;
        };
        Options::defaults(['a' => 'a'])->validator($callback)->allowUnexpected()->resolve(['b' => 'B'])->all();
        $this->assertSame(
            [
                ['name' => 'b', 'value' => 'B', 'wasExpected' => true],
                ['name' => 'a', 'value' => 'a', 'wasExpected' => true],
                ['name' => 'b', 'value' => 'B', 'wasExpected' => false],
            ],
            $params
        );

        // test that a specific (default) option is invalid when using combine()
        $params = [];
        $callback = function (string $name, $value, ?bool $wasExpected) use (&$params): bool {
            $params[] = ['name' => $name, 'value' => $value, 'wasExpected' => $wasExpected];
            return ($name != 'a'); // fail the 'a' option
        };
        $this->assertThrows(InvalidOptionException::class, function () use ($callback) {
            Options::defaults(['a' => 'a'])
                ->allowUnexpected()
                ->validator($callback)
                ->resolve(['b' => 'B', 'c' => 'C'])->all();
        });
        $this->assertSame(
            [
                ['name' => 'b', 'value' => 'B', 'wasExpected' => true],
                ['name' => 'c', 'value' => 'C', 'wasExpected' => true],
                ['name' => 'a', 'value' => 'a', 'wasExpected' => true],
            ],
            $params
        );

        // test that a specific (non-default) option is invalid when using combine()
        $params = [];
        $callback = function (string $name, $value, ?bool $wasExpected) use (&$params): bool {
            $params[] = ['name' => $name, 'value' => $value, 'wasExpected' => $wasExpected];
            return ($name != 'b'); // fail the 'b' option
        };
        $this->assertThrows(InvalidOptionException::class, function () use ($callback) {
            Options::defaults(['a' => 'a'])
                ->allowUnexpected()
                ->validator($callback)
                ->resolve(['b' => 'B', 'c' => 'C'])->all();
        });
        $this->assertSame(
            [
                ['name' => 'b', 'value' => 'B', 'wasExpected' => true],
            ],
            $params
        );

        // test that a specific default option when adding defaults
        $params = [];
        $callback = function (string $name, $value, ?bool $wasExpected) use (&$params): bool {
            $params[] = ['name' => $name, 'value' => $value, 'wasExpected' => $wasExpected];
            return ($name != 'a'); // fail the 'a' option
        };
        $this->assertThrows(InvalidOptionException::class, function () use ($callback) {
            Options::validator($callback)->defaults(['a' => 'a']);
        });
        $this->assertSame(
            [
                ['name' => 'a', 'value' => 'a', 'wasExpected' => true],
            ],
            $params
        );

        // test that a specific default option when overriding defaults
        $params = [];
        $callback = function (string $name, $value, ?bool $wasExpected) use (&$params): bool {
            $params[] = ['name' => $name, 'value' => $value, 'wasExpected' => $wasExpected];
            return ($value == 'a'); // fail the option without the value 'a'
        };
        $this->assertThrows(InvalidOptionException::class, function () use ($callback) {
            Options::validator($callback)->defaults(['a' => 'a'])->addDefaults(['a' => 'A']);
        });
        $this->assertSame(
            [
                ['name' => 'a', 'value' => 'a', 'wasExpected' => true], // first time is when defaults are set initially
                ['name' => 'a', 'value' => 'a', 'wasExpected' => true],
                ['name' => 'a', 'value' => 'A', 'wasExpected' => true],
            ],
            $params
        );

        // test that a specific default option when using parse()
        $params = [];
        $callback = function (string $name, $value, bool $wasExpected) use (&$params): bool {
            $params[] = ['name' => $name, 'value' => $value, 'wasExpected' => $wasExpected];
            return false;
        };
        $this->assertThrows(InvalidOptionException::class, function () use ($callback) {
            Options::validator($callback)->parse(['a' => 'a']);
        });
        $this->assertSame(
            [
                ['name' => 'a', 'value' => 'a', 'wasExpected' => true],
            ],
            $params
        );
    }
}
