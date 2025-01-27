# Options

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/options.svg?style=flat-square)](https://packagist.org/packages/code-distortion/options)
![PHP Version](https://img.shields.io/badge/PHP-7.0%20to%208.4-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/code-distortion/options/run-tests.yml?branch=master&style=flat-square)](https://github.com/code-distortion/options/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/options)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.1%20adopted-ff69b4.svg?style=flat-square)](.github/CODE_OF_CONDUCT.md)

***code-distortion/options*** is a PHP library for managing options in a flexible and expressive way.

```php
use CodeDistortion\Options\Options;

$results = Options::new('sendEmails sendSms !sendSlack plan=gold value=123.45')->all();

// [ 'sendEmails' => true,
//   'sendSms' => true,
//   'sendSlack' => false,
//   'plan' => 'gold',
//   'value' => 123.45 ]
```



## Table of Contents

* [Installation](#installation)
* [Usage](#usage)
    * [Simple Usage](#simple-usage)
    * [Programmatic Usage](#programmatic-usage)
* [Specifying Default Values](#specifying-default-values)
* [Restricting Options](#restricting-options)
* [Validation](#validation)
* [Fluent Interface](#fluent-interface)
* [Which Types Of Values Can I Specify?](#which-types-of-values-can-i-specify)
    * [Expressive String Format](#expressive-string-format)
    * [Array Key-Value-Pairs](#array-key-value-pairs)


## Installation

Install the package via composer:

```bash
composer require code-distortion/options
```



## Usage

### Simple Usage

Specify values by passing them to `Options::new()`. This will take your set of options and break them down into individual ones.

An instance of `Options` is returned. Call `all()` on it to get the resolved values.

```php
use CodeDistortion\Options\Options;

$results = Options::new('sendEmails sendSms !sendSlack')->all();
// or
$results = Options::new()->options('sendEmails sendSms !sendSlack')->all();

// [ 'sendEmails' => true,
//   'sendSms' => true,
//   'sendSlack' => false ]
```

`all()` will return the options in alphabetical order.



### Programmatic Usage

You can also interact with your `Options` instance in a more programmatic fashion.

```php
use CodeDistortion\Options\Options;

$options = Options::new('sendEmails sendSms !sendSlack');
// or
$options = Options::new()->options('sendEmails sendSms !sendSlack');

$has = $options->has('sendEmails');   // true
$has = $options->has('sendTweet');    // false
$value = $options->get('sendEmails'); // true
$value = $options->get('sendTweet');  // null
```

Calling `options()` multiple times will *replace* the previous options.

However you can *amend* the existing options by calling `amendOptions()`. This allows you to add or override existing options with new ones.

```php
$myOptions = 'sendEmails sendSms sendSlack';
$extraOptions = '!sendSms';
$options = Options::new()
    ->options($myOptions)
    ->amendOptions($extraOptions);
```



## Specifying Default Values

You can apply default values to use by passing them to `defaults()`. The default values will be applied for any options that aren't specified when calling `options()`.

It doesn't matter which order you call `defaults()` and `resolve()` in.

```php
$options = Options::new()
    ->defaults('sendEmails sendSms sendSlack')
    ->options('!sendEmails');

$options->get('sendEmails'); // false
$options->get('sendSms');    // true
```

Calling `defaults()` multiple times will *replace* the previous defaults.

However you can *amend* the existing defaults by calling `amendDefaults()`. This allows you to add or override existing defaults with new ones.

```php
$defaults = 'sendEmails sendSms sendSlack';
$quietModeDefaults = '!sendSms';
$options = Options::new()
    ->defaults($defaults)
    ->amendDefaults($quietModeDefaults);
```



## Restricting Options

You can restrict the possible options to those available in the defaults by calling `restrictUnexpected()`.

Options passed to `options()` that aren't present in the defaults will generate an `InvalidOptionException`.

```php
// the 'sendTweet' option is allowed because restrictUnexpected() was not called
$options = Options::new()
    ->defaults('sendEmails sendSms !sendSlack')
    ->options('sendTweet');

// InvalidOptionException: "The option "sendTweet" was not expected"
$options = Options::new()
    ->defaults('sendEmails sendSms !sendSlack')
    ->restrictUnexpected()
    ->options('sendTweet');
```



## Validation

You can validate the options by passing a callback to `validator()`.

Each option will be passed to your callback, letting you choose if it's valid.

If your callback returns false, that option will be ignored.

```php
$validatorCallback = function (string $name, $value, bool $wasExpected): bool {
    return is_bool($value); // ensure the value is a boolean
};

// sendEmails is ignored because it's not a boolean
Options::new()
    ->validator($validatorCallback)
    ->defaults('sendEmails sendSms !sendSlack')
    ->options('sendEmails=yes');
```

You can also pass a second parameter to `validator()` instructing it to throw an exception if a value is invalid.

```php
// InvalidOptionException: "The option "sendEmails" and/or it's value "yes" are not allowed"
Options::new()
    ->validator($validatorCallback, true) // <<<
    ->defaults('sendEmails sendSms !sendSlack')
    ->options('sendEmails=yes');
```



## Fluent Interface

The `options()`, `amendOptions()`, `defaults()`, `amendDefaults()`, `restrictUnexpected()` and `validator()` methods can be chained together, and can be called in any order.

```php
// instantiate
$options = new Options();
$options = new Options($myOptions);
$options = Options::new();
$options = Options::new($myOptions);

// call and chain any of these, in any order
$options->options($myOptions)
    ->amendOptions($extraOptions)
    ->defaults($defaults)
    ->amendDefaults($extraDefaults)
    ->restrictUnexpected()
    ->validator($validatorCallback);
```

```php
// then consult your Options instance like normal
$results = $options->all();
$has = $options->has('sendEmails');
$value = $options->get('sendEmails');
```



## Which Types Of Values Can I Specify?

### Expressive String Format

You can specify values as strings, with or without modifiers.

```php
'myVal' // ['myVal' => true]

// with modifiers
'+myVal' // ['myVal' => true]
'-myVal' // ['myVal' => false]
'!myVal' // ['myVal' => false]

// special values
'myVal=true'   // ['myVal' => true] (boolean true)
'myVal=false'  // ['myVal' => false] (boolean false)
'myVal=null'   // ['myVal' => null] (actual null)
'myVal=100'    // ['myVal' => 100] (an integer)
'myVal=123.45' // ['myVal' => 123.45] (a float)

// strings
'myVal='          // ['myVal' => '']
'myVal=somevalue' // ['myVal' => 'somevalue']

// quoted value strings
'myVal="true"'           // ['myVal' => 'true'] (not a boolean)
'myVal="some value"'     // ['myVal' => 'some value']
'myVal="some \"value\""' // ['myVal' => 'some "value"']
"myVal='some value'"     // ['myVal' => 'some value']
"myVal='some \'value\''" // ['myVal' => "some 'value'"]
"myVal=\"new\nline\""    // ['myVal' => "new\nline"]

// quoted key strings
'"my val"=true'     // ['my val' => true]
'"my \"val\""=true' // ['my "val"' => true]
"'my val'=true"     // ['my val' => true]
"'my \'val\''=true" // ["my 'val'" => true]
```

Multiple string values can be passed together at the same time, separated with spaces "` `" or a comma "`,`" (or both):

```php
'myVal1=abc +myVal2 -myVal3'   // ['myVal1' => 'abc', 'myVal2' => true, 'myVal3' => false']
'myVal1=abc,+myVal2,-myVal3'   // ['myVal1' => 'abc', 'myVal2' => true, 'myVal3' => false']
'myVal1=abc, +myVal2, -myVal3' // ['myVal1' => 'abc', 'myVal2' => true, 'myVal3' => false']
```



### Array Key-Value-Pairs

Regular expressions are used to examine the string values above. You may wish to use them for convenience, or use plain arrays like below for faster speed.

```php
['myVal' => true]
['myVal' => false]
['myVal' => 'some value']
['my val' => 'some value']
// etc
```

***Note:*** You can specify non-scalar values when passing key-value-pair arrays (e.g. nested arrays), however they aren't dealt with in any special way. They are currently treated like scalar values.



## Testing This Package

- Clone this package: `git clone https://github.com/code-distortion/options.git .`
- Run `composer install` to install dependencies
- Run the tests: `composer test`



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.



### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth, it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/code-distortion/options) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.



## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](.github/CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
