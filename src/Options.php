<?php

namespace CodeDistortion\Options;

use CodeDistortion\Options\Exceptions\UndefinedMethodException;
use CodeDistortion\Options\Exceptions\InvalidOptionException;

/**
 * Allow simple handling of runtime options.
 */
class Options
{
    /**
     * Each option found is cross-checked with this callback to see if it's valid.
     *
     * @var callable|null
     */
    protected $validator = null;

    /**
     * Should unexpected options be accepted? Otherwise an exception will be thrown.
     *
     * @var boolean
     */
    protected $allowUnexpected = false;

    /**
     * The default options to fall-back to.
     *
     * @var array|null
     */
    protected $defaults = null;

    /**
     * Custom options that are added to the defaults.
     *
     * @var array|null
     */
    protected $custom = null;

    /**
     * Resolved options - the custom added to the defaults.
     *
     * @var array|null
     */
    protected $resolved = null;



    /**
     * Positive modifier characters.
     *
     * @var array
     */
    const POSITIVE_MODIFIERS = ['+'];

    /**
     * Negative modifier characters.
     *
     * @var array
     */
    const NEGATIVE_MODIFIERS = ['!', '-'];

    /**
     * Characters that separate options within a string.
     *
     * @var array
     */
    const DIVIDERS = [' ', ',', "\r", "\n", "\t"];

    /**
     * Strings that have special meanings.
     */
    const SPECIAL_VALUES = [
        'true' => true,
        'false' => false,
        'null' => null,
    ];



    /**
     * Process NON-STATIC calls to various methods.
     *
     * @param string $method The method to call.
     * @param array  $args   The arguments to pass.
     *
     * @return mixed
     *
     * @throws UndefinedMethodException Thrown when the called method does not exist.
     */
    public function __call(string $method, array $args)
    {
        if (method_exists(static::class, 'call'.$method)) {
            $toCall = [$this, 'call'.$method];
            if (is_callable($toCall)) {
                return call_user_func_array($toCall, $args);
            }
        }
        throw UndefinedMethodException::undefinedMethod($method);
    }

    /**
     * Process STATIC calls to various methods.
     *
     * @param string $method The method to call.
     * @param array  $args   The arguments to pass.
     *
     * @return mixed
     *
     * @throws UndefinedMethodException Thrown when the called method does not exist.
     */
    public static function __callStatic(string $method, array $args)
    {
        if (method_exists(static::class, 'call'.$method)) {
            $toCall = [new static(), 'call'.$method];
            if (is_callable($toCall)) {
                return call_user_func_array($toCall, $args);
            }
        }
        throw UndefinedMethodException::undefinedStaticMethod($method);
    }





    /**
     * Let the caller specify a callback that's used to check each option against.
     *
     * @param callable|null $validator The callback to check each option and value with.
     *
     * @return static
     */
    private function callValidator(?callable $validator): self
    {
        $this->validator = $validator;
        $this->resolved = null; // force reResolve to re-evaluate
        return $this; // chain-able
    }

    /**
     * Let the caller specify whether unexpected options are allowed.
     *
     * @param boolean $allow Whether unexpected options are allowed or not.
     *
     * @return static
     */
    private function callAllowUnexpected(bool $allow = true): self
    {
        $this->allowUnexpected = $allow;
        $this->resolved = null; // force reResolve to re-evaluate
        return $this; // chain-able
    }

    /**
     * Let the caller specify the default values to fall-back to.
     *
     * @param mixed ...$args The default values to store.
     *
     * @return static
     *
     * @throws InvalidOptionException Thrown when an option isn't valid.
     */
    private function callDefaults(...$args): self
    {
        $this->defaults = (count($args) ? static::combineSets($args) : null);
        $this->resolved = null; // force reResolve to re-evaluate
        return $this; // chain-able
    }

    /**
     * Let the caller add extra defaults.
     *
     * @param mixed ...$args The default values to store.
     *
     * @return static
     *
     * @throws InvalidOptionException Thrown when an option isn't valid.
     */
    private function callAddDefaults(...$args): self
    {
        if (count($args)) {
            $this->defaults = static::combineSets($args, $this->defaults, true);
            $this->resolved = null; // force reResolve to re-evaluate
        }
        return $this; // chain-able
    }

    /**
     * Return the default option values.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return (is_array($this->defaults) ? $this->defaults : []);
    }

    /**
     * Resolve the given options (includes the default values).
     *
     * @param mixed ...$args The sets of options to combine. The last ones take precedence.
     *
     * @return static
     *
     * @throws InvalidOptionException Thrown when an option isn't valid or allowed.
     */
    private function callResolve(...$args): self
    {
        $this->custom = (count($args) ? static::combineSets($args) : null);
        $this->resolved = null; // force reResolve to re-evaluate
        $this->reResolve();
        return $this; // chain-able
    }

    /**
     * Returns all the resolved option values.
     *
     * @return array The resolved options.
     */
    public function all(): array
    {
        try {
            return $this->reResolve();
        } catch (InvalidOptionException $e) {
            return [];
        }
    }

    /**
     * Returns a particular resolved option value.
     *
     * @param string $name The name of the option to return.
     *
     * @return mixed
     */
    public function get(string $name)
    {
        try {
            $this->reResolve();
        } catch (InvalidOptionException $e) {
            // ignore
        }

        return (
            (is_array($this->resolved)) && (array_key_exists($name, $this->resolved))
            ? $this->resolved[$name]
            : null
        );
    }

    /**
     * Returns a particular default option value.
     *
     * @param string $name The name of the option to return.
     *
     * @return mixed
     */
    public function getDefault(string $name)
    {
        return (
            (is_array($this->defaults)) && (array_key_exists($name, $this->defaults))
            ? $this->defaults[$name]
            : null
        );
    }

    /**
     * Returns a particular custom option value.
     *
     * @param string $name The name of the option to return.
     *
     * @return mixed
     */
    public function getCustom(string $name)
    {
        return (
            (is_array($this->custom)) && (array_key_exists($name, $this->custom))
            ? $this->custom[$name]
            : null
        );
    }

    /**
     * Checks whether a particular resolved option exists.
     *
     * @param string $name The name of the option to check.
     *
     * @return boolean
     */
    public function has(string $name): bool
    {
        try {
            $this->reResolve();
        } catch (InvalidOptionException $e) {
            // ignore
        }

        return (is_array($this->resolved)) && (array_key_exists($name, $this->resolved));
    }

    /**
     * Checks whether a particular default option exists.
     *
     * @param string $name The name of the option to check.
     *
     * @return boolean
     */
    public function hasDefault(string $name): bool
    {
        return (is_array($this->defaults)) && (array_key_exists($name, $this->defaults));
    }

    /**
     * Checks whether a particular custom option exists.
     *
     * @param string $name The name of the option to check.
     *
     * @return boolean
     */
    public function hasCustom(string $name): bool
    {
        return (is_array($this->custom)) && (array_key_exists($name, $this->custom));
    }

    /**
     * Parse the given options (but ignore the default values and don't store the result).
     *
     * @param mixed ...$args The sets of options to resolve. The last ones take precedence.
     *
     * @return array The parsed options
     *
     * @throws InvalidOptionException Thrown when an option isn't valid.
     */
    private function callParse(...$args): array
    {
        return static::combineSets($args);
    }





    /**
     * Combine the custom options with the defaults.
     *
     * @return array
     *
     * @throws InvalidOptionException Thrown when an option isn't valid or allowed.
     */
    protected function reResolve(): array
    {
        if (is_null($this->resolved)) {
            $this->resolved = static::combineSets([$this->custom], $this->defaults, $this->allowUnexpected);
        }
        return $this->resolved;
    }

    /**
     * Combine the given options.
     *
     * @param array      $optionSets      The sets of options to combine. The last ones take precedence.
     * @param array|null $defaults        The default options to fall-back to.
     * @param boolean    $allowUnexpected Should an exception be thrown in an unexpected option is found?.
     *
     * @return array The combined options
     *
     * @throws InvalidOptionException Thrown when an option is given but isn't allowed.
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
                    throw InvalidOptionException::unexpectedOption($key);
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
     * Parse the given option-set (a string, or an array containing strings or key-value pairs).
     *
     * @param string|array $optionSet The set of options to parse.
     *
     * @return array
     */
    protected static function parseSet($optionSet)
    {
        $pregDividerChars = preg_quote(implode(static::DIVIDERS));

        // if the option-set is actually a string, turn it into an array ready to loop through
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
                    }

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
     * Validate the given option key + value against the validation callback.
     *
     * @param string  $key        The option name.
     * @param mixed   $value      The option value.
     * @param boolean $isExpected Was this option expected (ie. specified in the defaults?).
     *
     * @return void
     *
     * @throws InvalidOptionException Thrown when the given option or value isn't valid.
     */
    protected function validateOption(string $key, $value, bool $isExpected): void
    {
        if (is_callable($this->validator)) {
            $isValid = (bool) ($this->validator)($key, $value, $isExpected);
            if (!$isValid) {
                throw InvalidOptionException::invalidOptionOrValue($key, $value);
            }
        }
    }
}
