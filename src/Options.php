<?php

namespace CodeDistortion\Options;

use Exception;

/**
 * Allow simple handling of runtime options
  */
class Options
{
    /**
     * Each option found is cross-checked with this callback to see if it's valid
     *
     * @var ?callable
     */
    protected $validator = null;

    /**
     * Should unexpected options be accepted? Otherwise an exception will be thrown
     *
     * @var boolean
     */
    protected $allowUnexpected = false;

    /**
     * The default values to fall-back to
     *
     * @var ?array
     */
    protected $defaults = null;


    /**
     * Positive modifier characters
     *
     * @var array
     */
    const POSITIVE_MODIFIERS = ['+'];

    /**
     * Negative modifier characters
     *
     * @var array
     */
    const NEGATIVE_MODIFIERS = ['!', '-'];

    /**
     * Characters that separate options within a string
     *
     * @var array
     */
    const DIVIDERS = [' ', ',', "\r", "\n", "\t"];

    /**
     * Strings that have special meanings
     */
    const SPECIAL_VALUES = [
        'true' => true,
        'false' => false,
        'null' => null,
    ];



    /**
     * Process NON-STATIC calls to various methods
     *
     * @param string $method The method to call.
     * @param array  $args   The arguments to pass.
     * @return mixed
     * @throws Exception Thrown when the called method does not exist.
     */
    public function __call(string $method, array $args)
    {
        if (method_exists(static::class, 'call'.$method)) {
            $toCall = [$this, 'call'.$method];
            if (is_callable($toCall)) {
                return call_user_func_array($toCall, $args);
            }
        }
        throw new Exception('Undefined method: '.static::class.'::'.$method.'()');
    }

    /**
     * Process STATIC calls to various methods
     *
     * @param string $method The method to call.
     * @param array  $args   The arguments to pass.
     * @return mixed
     * @throws Exception Thrown when the called method does not exist.
     */
    public static function __callStatic(string $method, array $args)
    {
        if (method_exists(static::class, 'call'.$method)) {
            $toCall = [new static(), 'call'.$method];
            if (is_callable($toCall)) {
                return call_user_func_array($toCall, $args);
            }
        }
        throw new Exception('Undefined method: '.static::class.'::'.$method.'()');
    }





    /**
     * Let the caller specify a callback that's used to check each option against
     *
     * @param callable|null $validator The callback to check each option and value with.
     * @return static
     */
    private function callValidator(?callable $validator): self
    {
        $this->validator = $validator;
        return $this; // chainable
    }

    /**
     * Let the caller specify whether unexpected options are allowed
     *
     * @param boolean $allow Whether unexpected options are allowed or not.
     * @return static
     */
    private function callAllowUnexpected(bool $allow = true): self
    {
        $this->allowUnexpected = $allow;
        return $this; // chainable
    }

    /**
     * Let the caller specify the default values to fall-back to
     *
     * @param mixed ...$args The default values to store.
     * @return static
     */
    private function callDefaults(...$args): self
    {
        $this->defaults = (count($args) ? static::combineSets($args) : null);
        return $this; // chainable
    }

    /**
     * Let the caller add extra defaults
     *
     * @param mixed ...$args The default values to store.
     * @return static
     */
    private function callAddDefaults(...$args): self
    {
        if (count($args)) {
            $this->defaults = static::combineSets($args, $this->defaults, true);
        }
        return $this; // chainable
    }

    /**
     * Let the caller specify the default values to fall-back to
     *
     * @return ?array
     */
    public function getDefaults(): ?array
    {
        return $this->defaults;
    }

    /**
     * Resolve the given options (includes the default values)
     *
     * @param mixed ...$args The sets of options to combine. The last ones take precedence.
     * @return array The resolved options
     */
    private function callResolve(...$args)
    {
        return static::combineSets($args, $this->defaults, $this->allowUnexpected);
    }

    /**
     * Parse the given options (but ignore the default values)
     *
     * @param mixed ...$args The sets of options to resolve. The last ones take precedence.
     * @return array The parsed options
     */
    private function callParse(...$args)
    {
        return static::combineSets($args);
    }





    /**
     * Combine the given options
     *
     * @param array      $optionSets      The sets of options to combine. The last ones take precedence.
     * @param array|null $defaults        The default options to fall-back to.
     * @param boolean    $allowUnexpected Should an exception be thrown in an unexpected option is found?.
     * @return array The combined options
     * @throws Exception Thrown when an option was given but it isn't allowed.
     */
    protected function combineSets(array $optionSets, array $defaults = null, bool $allowUnexpected = false)
    {
        $hasDefaults = false;
        if (is_array($defaults)) {
            // add the defaults to the sets to be looped through
            $optionSets = array_merge(['defaults' => $defaults], array_values($optionSets));
            $hasDefaults = true;
        }

        $resultOptions = [];
        foreach ($optionSets as $setName => $optionSet) {

            // add this set of options to the $resultOptions ready to return
            $parsedSet = static::parseSet($optionSet);
            foreach ($parsedSet as $key => $value) {

                // detect when an option doesn't have a default value
                $isDefaultSet = ($setName === 'defaults');
                $isExpected = !(($hasDefaults)
                    && (!$isDefaultSet)
                    && (!array_key_exists($key, $resultOptions)));

                // stop if unexpected (and not allowed)
                if ((!$isExpected) && (!$allowUnexpected)) {
                    throw new Exception('The option "'.$key.'" was not expected');
                }

                // validate the $key + $value
                $this->validateOption($key, $value, $isExpected);

                // record the key + value
                $resultOptions[$key] = $value;
            }
        }
        return $resultOptions;
    }

    /**
     * Parse the given option-set (a string, or an array containing strings or key-value pairs)
     *
     * @param string|array $optionSet The set of options to parse.
     * @return array
     */
    protected static function parseSet($optionSet)
    {
        $pregModifierChars = preg_quote(implode(array_merge(static::POSITIVE_MODIFIERS, static::NEGATIVE_MODIFIERS)));
        $pregDividerChars = preg_quote(implode(static::DIVIDERS));

        // if the option-set is actually a string, turn it into an array ready to loop throuh
        $optionSet = (is_array($optionSet) ? $optionSet : [$optionSet]);

        // check what is meant by each part - they're either key-value-pairs or strings that need to be broken down
        $parsedSet = [];
        foreach ($optionSet as $key => $value) {

            // interpret options from STRINGS
            if ((is_int($key)) && (is_string($value))) {

                $regex = '/'
                    .'(' // key
                        .'"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|' // double-quotes - allow escaping
                        ."'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'|" // single-quotes - allow escaping
                        .'([^'.$pregDividerChars.'=]+)'      // no quotes
                    .')(=(' // value
                        .'"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|' // double-quotes - allow escaping
                        ."'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'|" // single-quotes - allow escaping
                        .'([^'.$pregDividerChars.']*)'       // no quotes
                    .'))?'
                    .'/s';
                preg_match_all($regex, $value, $matches);
                foreach (array_keys($matches[0]) as $index) {

                    $key = '';
                    foreach ([2, 3, 4] as $valueIndex) {
                        if (mb_strlen($matches[$valueIndex][$index])) {

                            // no quotes
                            if ($valueIndex == 4) {
                                $key = trim($matches[$valueIndex][$index]);
                            // un-escape the quoted value
                            } else {
                                $key = stripslashes($matches[$valueIndex][$index]);
                            }
                            break;
                        }
                    };

                    // has a value
                    $value = '';
                    $hadEquals = false;
                    if (mb_strlen($matches[5][$index])) {

                        $hadEquals = true;

                        // the value with double-quotes, single-quotes or no quotes
                        foreach ([7, 8, 9] as $valueIndex) {
                            if (mb_strlen($matches[$valueIndex][$index])) {

                                // give special meaning to certain values when given without quotes
                                // (eg. a=true instead of a="true")
                                if ($valueIndex == 9) {

                                    $value = $matches[$valueIndex][$index];

                                    // check for strings with special meanings (true, false, null)
                                    $tempValue = mb_strtolower($value);
                                    if (array_key_exists($tempValue, static::SPECIAL_VALUES)) {
                                        $value = static::SPECIAL_VALUES[$tempValue];
                                    // check for integer numbers
                                    } elseif (preg_match('/^-?[0-9]+$/', $value)) {
                                        $value = (int) $value;
                                    // check for floating numbers
                                    } elseif (preg_match('/^-?[0-9]+\.[0-9]+$/', $value)) {
                                        $value = (float) $value;
                                    }
                                // or values given with double or single quotes
                                } else {
                                    // un-escape the quoted value
                                    $value = stripslashes($matches[$valueIndex][$index]);
                                }
                                break;
                            }
                        }
                    // or no given value
                    } else {
                        $value = true; // if it starts with a modifier (eg. '!') then it will be updated below
                    }



                    // apply option modifiers
                    // detect whether any of the options start with a modifier
                    if (!$hadEquals) {
                        $firstChar = mb_substr($key, 0, 1);
                        if (in_array($firstChar, static::NEGATIVE_MODIFIERS)) {
                            $key = mb_substr($key, 1);
                            $value = false;
                        } elseif (in_array($firstChar, static::POSITIVE_MODIFIERS)) {
                            $key = mb_substr($key, 1);
                            $value = true;
                        }
                    }

                    // record the option if it's key wasn't empty after removing the modifiers
                    if (mb_strlen($key)) {
                        $parsedSet[$key] = $value;
                    }
                }
            // or it's a key-value-pair
            } elseif (!is_int($key)) {
                $key = trim($key);
                if (mb_strlen($key)) {
                    $parsedSet[$key] = $value;
                }
            }
        }

        return $parsedSet;
    }

    /**
     * Validate the given option key + value against the validation callback
     *
     * @param string  $key        The option name.
     * @param mixed   $value      The option value.
     * @param boolean $isExpected Was this option expected (ie. specified in the defaults?).
     * @return void
     * @throws Exception Thrown when the given option or value isn't valid.
     */
    protected function validateOption(string $key, $value, bool $isExpected): void
    {
        if (is_callable($this->validator)) {
            $isValid = (bool) ($this->validator)($key, $value, $isExpected);
            if (!$isValid) {
                throw new Exception('The option "'.$key.'" and/or it\'s value "'.$value.'" are not allowed');
            }
        }
    }
}
