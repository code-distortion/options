<?php

declare(strict_types=1);

namespace CodeDistortion\Options;

use CodeDistortion\DICaller\DICaller;
use CodeDistortion\Options\Exceptions\InvalidOptionException;

/**
 * Identify and resolve options (e.g. command-line options) with a fluent interface.
 */
class Options
{
    /** @var callable|null Each option found is cross-checked with this callback to see if it's valid. */
    private $validator = null;

    /** @var boolean Should an exception be thrown if the validator returns false?. */
    private $throwValidatorException = false;

    /** @var boolean Should unexpected options be accepted? Otherwise, an exception will be thrown. */
    private $allowUnexpected = true;

    /** @var boolean Should an exception be thrown if an unexpected option is found?. */
    private $throwUnexpectedException = false;



    /** @var array<string, mixed>|null The default options to fall back to. These are parsed, but un-validated. */
    private $defaults = null;

    /** @var array<string, mixed>|null Custom options that are added to the defaults. These are parsed, but un-validated. */
    private $custom = null;

    /** @var array<string, mixed>|null The resolved option values. Custom combined with the defaults. Parsed and validated. */
    private $resolved = null;



    /** @var string[] Positive modifier characters(single characters only). */
    const POSITIVE_MODIFIERS = ['+'];

    /** @var string[] Negative modifier characters (single characters only). */
    const NEGATIVE_MODIFIERS = ['!', '-'];

    /** @var string[] Characters that separate options within a string. */
    const DIVIDERS = [' ', ',', "\r", "\n", "\t"];

    /** Strings that have special meanings (values are compared in a case-insensitive way. Use lowercase keys here). */
    const SPECIAL_VALUES = [
        'true' => true,
        'false' => false,
        'null' => null,
    ];





    /**
     * Constructor.
     *
     * @param mixed ...$args The sets of options to resolve. The latter ones take precedence.
     */
    public function __construct(...$args)
    {
        $this->options(...$args);
    }



    /**
     * Alternative constructor.
     *
     * @param mixed ...$args The sets of options to resolve. The latter ones take precedence.
     * @return self
     */
    public static function new(...$args): self
    {
        return new self(...$args);
    }





    /**
     * Specify options to resolve.
     *
     * @param mixed ...$args The sets of options to resolve. The latter ones take precedence.
     * @return self
     */
    public function options(...$args): self
    {
        // replace the custom options with the new ones
        $this->custom = (count($args) !== 0)
            ? self::parseArgs($args)
            : null;
        $this->forceReevaluateBeforeReading();

        return $this;
    }

    /**
     * Add or override the existing set of options.
     *
     * @param mixed ...$args The sets of options to resolve. The latter ones take precedence.
     * @return self
     */
    public function amendOptions(...$args): self
    {
        if (count($args) !== 0) {
            $this->custom = array_merge(
                $this->custom ?? [],
                self::parseArgs($args)
            );
            $this->forceReevaluateBeforeReading();
        }

        return $this;
    }





    /**
     * Let the caller specify the default values to fall back to.
     *
     * @param mixed ...$args The default values to store. The latter ones take precedence.
     * @return self
     */
    public function defaults(...$args): self
    {
        // replace the defaults with the new ones
        $this->defaults = (count($args) !== 0)
            ? self::parseArgs($args)
            : null;
        $this->forceReevaluateBeforeReading();

        return $this;
    }

    /**
     * Add or override the existing set of defaults.
     *
     * @param mixed ...$args The default values to store. The latter ones take precedence.
     * @return self
     */
    public function amendDefaults(...$args): self
    {
        if (count($args) !== 0) {
            $this->defaults = array_merge(
                $this->defaults ?? [],
                self::parseArgs($args)
            );
            $this->forceReevaluateBeforeReading();
        }

        return $this;
    }

//    /**
//     * Return the default option values.
//     *
//     * @return array
//     */
//    public function getDefaults(): array
//    {
//        return is_array($this->defaults)
//            ? $this->defaults
//            : [];
//    }
//
//    /**
//     * Checks whether a particular default option exists.
//     *
//     * @param string $name The name of the option to check.
//     * @return boolean
//     */
//    public function hasDefault(string $name): bool
//    {
//        return (is_array($this->defaults)) && (array_key_exists($name, $this->defaults));
//    }
//
//    /**
//     * Returns a particular default option value.
//     *
//     * @param string $name The name of the option to return.
//     * @return mixed
//     */
//    public function getDefault(string $name)
//    {
//        return (is_array($this->defaults) && array_key_exists($name, $this->defaults))
//            ? $this->defaults[$name]
//            : null;
//    }





    /**
     * Let the caller specify whether unexpected options (not present in the defaults) are restricted or not.
     *
     * @param boolean $restrict       Whether unexpected options are restricted or not.
     * @param boolean $throwException Should an exception be thrown if an unexpected option is found?.
     * @return self
     */
    public function restrictUnexpected(bool $restrict = true, bool $throwException = false): self
    {
        $this->allowUnexpected = !$restrict;
        $this->throwUnexpectedException = $throwException;
        $this->forceReevaluateBeforeReading();

        return $this;
    }





    /**
     * Let the caller specify a callback that's used to check each option against.
     *
     * @param callable|null $validator      The callback to check each option and value with.
     * @param boolean       $throwException Should an exception be thrown if the validator returns false?.
     * @return self
     */
    public function validator($validator, bool $throwException = false): self
    {
        $this->validator = $validator;
        $this->throwValidatorException = $throwException;
        $this->forceReevaluateBeforeReading();

        return $this;
    }





    /**
     * Returns all the resolved option values.
     *
     * @return array<string, mixed> The resolved options.
     */
    public function all(): array
    {
        $this->reevaluate();

        return $this->resolved ?? [];
    }

    /**
     * Checks whether a particular resolved option exists.
     *
     * @param string $name The name of the option to check.
     * @return boolean
     */
    public function has(string $name): bool
    {
        $this->reevaluate();

        return (is_array($this->resolved)) && (array_key_exists($name, $this->resolved));
    }

    /**
     * Returns a particular resolved option.
     *
     * @param string $name The name of the option to return.
     * @return mixed
     */
    public function get(string $name)
    {
        $this->reevaluate();

        return (is_array($this->resolved) && array_key_exists($name, $this->resolved))
            ? $this->resolved[$name]
            : null;
    }





//    /**
//     * Checks whether a particular custom option exists.
//     *
//     * @param string $name The name of the option to check.
//     * @return boolean
//     */
//    public function hasCustom(string $name): bool
//    {
//        return (is_array($this->custom)) && (array_key_exists($name, $this->custom));
//    }
//
//    /**
//     * Returns a particular custom option value.
//     *
//     * @param string $name The name of the option to return.
//     * @return mixed
//     */
//    public function getCustom(string $name)
//    {
//        return (is_array($this->custom) && array_key_exists($name, $this->custom))
//            ? $this->custom[$name]
//            : null;
//    }





    /**
     * Parse the given arguments (strings, or arrays containing strings or key-value pairs) into individual options.
     *
     * @param array<integer|string,mixed> $args The set of arguments to parse.
     * @return array<string, mixed>
     * @throws InvalidOptionException Thrown when an option array is passed without string values or keys.
     */
    private static function parseArgs(array $args): array
    {
        $optionSet = self::flattenArgs($args);

        $pregDividerChars = implode(self::DIVIDERS);
        // @infection-ignore-all remove preg_quote()
        // (it's correct to apply, but none of the dividers currently need quoting)
        $pregDividerChars = preg_quote($pregDividerChars);

        // check what is meant by each part - they're either key-value-pairs or strings that need to be broken down
        $parsedSet = [];
        foreach ($optionSet as $key => $value) {

            // interpret options from STRINGS
            if (is_int($key)) {
                if (is_string($value)) {

                    $doubleQuotes = '"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"'; // double-quotes - allow escaping
                    $singleQuotes = "'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'"; // single-quotes - allow escaping
                    $noQuotes1 = "([^$pregDividerChars=]+)"; // no quotes
                    $noQuotes2 = "([^$pregDividerChars]*)"; // no quotes
                    $regex = '/'
                        . "($doubleQuotes|$singleQuotes|$noQuotes1)" // key
                        . "(=($doubleQuotes|$singleQuotes|$noQuotes2))?" // value (optional)
                        . '/s';

                    preg_match_all($regex, $value, $matches);

                    // @infection-ignore-all $matches[0] -> $matches[1]
                    // (this is ok because the indexes of $matches[0] and $matches[1] are the same)
                    $indexes = array_keys($matches[0]);
                    foreach ($indexes as $index) {

                        $quotedKey = $matches[2][$index];
                        $quotedKey .= $matches[3][$index];
                        $unquotedKey = $matches[4][$index];
                        $key = ($quotedKey !== '')
                            ? stripslashes($quotedKey) // un-escape the quoted key
                            : $unquotedKey;

                        // has a value
                        $hadEquals = false;
                        if ($matches[5][$index] !== '') {

                            $hadEquals = true;

                            $quotedValue = $matches[7][$index];
                            $quotedValue .= $matches[8][$index];
                            $unquotedValue = $matches[9][$index];

                            if ($quotedValue !== '') {
                                $value = stripslashes($quotedValue); // un-escape the quoted value
                            } else {

                                $value = $unquotedValue;

                                // check for strings with special meanings (true, false, null)

                                // @infection-ignore-all remove preg_quote()
                                // (mb_strtolower() is correct to use, but none of the special values currently
                                // have multibyte characters)
                                $tempValue = mb_strtolower($value);
                                if (array_key_exists($tempValue, self::SPECIAL_VALUES)) {
                                    $value = self::SPECIAL_VALUES[$tempValue];
                                    // check for integer numbers
                                } elseif (preg_match('/^-?[0-9]+$/', $value) === 1) {
                                    $value = (int) $value;
                                    // check for floating numbers
                                } elseif (preg_match('/^-?[0-9]+\.[0-9]+$/', $value) === 1) {
                                    $value = (float) $value;
                                }
                            }
                        // or no given value
                        } else {
                            $value = true; // if it starts with a modifier (e.g. '!') then it will be updated below
                        }

                        // apply option modifiers
                        // detect whether any of the options start with a modifier
                        if (!$hadEquals) {
                            // @infection-ignore-all mb_substr() -> substr()
                            // (mb_substr() is correct to use, but none of the modifiers have multibyte characters)
                            $firstChar = mb_substr($key, 0, 1);
                            if (in_array($firstChar, self::NEGATIVE_MODIFIERS, true)) {
                                // @infection-ignore-all mb_substr() -> substr()
                                // (mb_substr() is correct to use, but none of the modifiers have multibyte characters)
                                $key = mb_substr($key, 1);
                                $value = false;
                            } elseif (in_array($firstChar, self::POSITIVE_MODIFIERS, true)) {
                                // @infection-ignore-all mb_substr() -> substr()
                                // (mb_substr() is correct to use, but none of the modifiers have multibyte characters)
                                $key = mb_substr($key, 1);
                                $value = true;
                            }
                        }

                        // record the option if it's key wasn't empty after removing the modifiers
                        if ($key !== '') {
                            $parsedSet[$key] = $value;
                        }
                    }
                } else {
                    throw InvalidOptionException::optionArraysMustContainStringsOrHaveKeys();
                }

            // or it's a key-value-pair
            } else {
                $key = trim($key);
                if ($key !== '') {
                    $parsedSet[$key] = $value;
                }
            }
        }

        return $parsedSet;
    }

    /**
     * Take the input from the user, and flatten it into a single array for processing.
     *
     * The user may pass a string, an array of strings, or an array of key-value pairs.
     *
     * @param array<integer|string,mixed> $args The set of options to flatten.
     * @return array<integer|string,mixed>
     */
    private static function flattenArgs(array $args): array
    {
        $flattened = [];
        foreach ($args as $arg) {

            if (is_array($arg)) {
                $flattened = array_merge($flattened, $arg);
            } else {
                $flattened[] = $arg;
            }
        }

        return $flattened;
    }





    /**
     * Make sure the options are re-evaluated next time they're retrieved.
     *
     * @return void
     */
    private function forceReevaluateBeforeReading()
    {
        $this->resolved = null;
    }

    /**
     * Combine the custom options with the defaults, and validate.
     *
     * @return void
     * @throws InvalidOptionException Thrown when an option isn't valid or allowed.
     */
    private function reevaluate()
    {
        if (!is_null($this->resolved)) {
            return;
        }



        $defaults = $this->defaults ?? [];
        $custom = $this->custom ?? [];

        $allKeys = array_merge(array_keys($defaults), array_keys($custom));
        // @infection-ignore-all remove (bool)
        $allKeys = array_unique($allKeys);
        sort($allKeys);

        $resolved = [];
        foreach ($allKeys as $key) {

            $hasDefault = $isExpected = array_key_exists($key, $defaults);
            $hasCustom = array_key_exists($key, $custom);

            // stop if unexpected (when unexpected values aren't allowed)
            if ((!$isExpected) && (!$this->allowUnexpected)) {
                if ($this->throwUnexpectedException) {
                    throw InvalidOptionException::unexpectedOption($key);
                }
                continue;
            }

            // try the default value
            // this may be overridden below, but it will validate the default value,
            // alerting the developer to an issue if there is one
            if ($hasDefault) {
                $value = $defaults[$key];
                if ($this->isOptionValid($key, $value, true)) {
                    $resolved[$key] = $value;
                }
            }

            // try the custom value - overriding the default (if set)
            if ($hasCustom) {
                $value = $custom[$key];
                if ($this->isOptionValid($key, $value, $isExpected)) {
                    $resolved[$key] = $value;
                }
            }
        }

        $this->resolved = $resolved;
    }

    /**
     * Validate the given option key + value against the validation callback.
     *
     * @param string  $key        The option name.
     * @param mixed   $value      The option value.
     * @param boolean $isExpected Was this option expected (i.e. specified in the defaults?).
     * @return boolean
     * @throws InvalidOptionException Thrown when the given option or value isn't valid.
     */
    private function isOptionValid(string $key, $value, bool $isExpected): bool
    {
        $caller = DICaller::new($this->validator)
            ->registerByName('name', $key)
            ->registerByName('value', $value)
            ->registerByName('wasExpected', $isExpected);

        // nothing to validate with
        if (!$caller->canCall()) {
            return true;
        }

        // @infection-ignore-all remove (bool)
        $isValid = (bool) $caller->call();
        if (!$isValid) {
            if ($this->throwValidatorException) {
                throw InvalidOptionException::invalidOptionOrValue($key, $value);
            } else {
                return false;
            }
        }

        return true;
    }
}
