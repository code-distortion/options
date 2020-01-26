# Options

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/options.svg?style=flat-square)](https://packagist.org/packages/code-distortion/options)
![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/options?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/options/run-tests?label=tests&style=flat-square)](https://github.com/code-distortion/options/actions)
[![Buy us a tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://offset.earth/treeware?gift-trees)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)

***code-distortion/options*** is a PHP library for managing options in a simple, flexible and expressive way.

``` php
// You can format your options in expressive string format
$results = Options::defaults('sendAlerts dailyLimit=123.45 !applyDailyLimit currency=AUD plan="Silver Plan"')->resolve('currency=USD plan="Gold Plan"');

// $results
// [ 'sendAlerts' => true,
//   'dailyLimit' => 123.45,
//   'applyDailyLimit' => false,
//   'currency' => 'USD',
//   'plan' => 'Gold Plan' ]

// This is equivalent to using array key-value-pairs
$results = Options::defaults([
    'sendAlerts' => true,
    'dailyLimit' => 123.45,
    'applyDailyLimit' => false,
    'currency' => 'AUD',
    'plan' => 'Silver Plan'
])->resolve([
    'currency' => 'USD',
    'plan' => 'Gold Plan'
]);
```

## Installation

Install the package via composer:

```bash
composer require code-distortion/options
```

## Usage

### Specifying default values

You can optionally specify default fall-back values to use by calling `defaults()`:

``` php
use CodeDistortion\Options\Options;

$defaults = 'sendEmails sendSms !sendSnail';
$options = Options::defaults($defaults); // sets the defaults for the first time, or replaces them completely

// you can alter the current default values
$quietModeDefaults = '!sendSms';
$options->addDefaults($quietModeDefaults); // adds to the existing defaults - overriding where necessary

// retrieve the processed option defaults as an array
$options->getDefaults();
```

### Resolving a set of options

To combine default values and custom values, use the `resolve()` method:

``` php
// resolve a combined set of values
$defaults = 'sendEmails sendSms !sendSnail';
$userPrefs = '!sendEmails sendSms';
$options = Options::defaults($defaults)->resolve($userPrefs);

// retrieve all the option values
$results = $options->all();
// [ 'sendEmails' => false,
//   'sendSms' => true,
//   'sendSnail' => false ]

// retrieve a particular option's value
$value = $options->get('sendEmails'); // false
$value = $options->get('sendTweet');  // null
$value = $options->getDefault('sendEmails'); // true
$value = $options->getCustom('sendEmails');  // false

// check if a particular option is set
$has = $options->has('sendEmails'); // true
$has = $options->has('sendTweet');  // false
$has = $options->hasDefault('sendSnail'); // true
$has = $options->hasCustom('sendSnail');  // false
````

***Note:*** If you specify default values, any values passed to `resolve()` that aren't present in the defaults will generate an exception unless `allowUnexpected()` is called before hand:

``` php
// the 'sendTweet' option is allowed because allowUnexpected() was called
$results = Options::defaults('sendEmails sendSms !sendSnail')->allowUnexpected()->validator('sendTweet');
```

The `parse()` method is also available if you would like to skip the defaults and just take advantage of the string parsing functionality:

``` php
$results = Option::parse('sendEmails sendSms !sendSnail'); // ['sendEmails' => true, 'sendSms' => true, 'sendSnail' => false]
```

### Value types

You can specify option values as either [expressive strings](#expressive-string-format) or arrays of [key-value-pairs](#array-key-value-pairs).

#### Expressive string format

``` php
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

Multiple string values can joined together and separated with either spaces "` `" or a comma `,` (or both):

``` php
'myVal1=abc +myVal2 -myVal3'   // ['myVal1' => 'abc', 'myVal2' => true, 'myVal3' => false']
'myVal1=abc,+myVal2,-myVal3'   // ['myVal1' => 'abc', 'myVal2' => true, 'myVal3' => false']
'myVal1=abc, +myVal2, -myVal3' // ['myVal1' => 'abc', 'myVal2' => true, 'myVal3' => false']
```

***Note:*** Regular expressions are used to examine the string values above. You may wish to use them for convenience, or use plain arrays like below for faster speed.

#### Array key-value-pairs

You may specify values simply as arrays:

``` php
['myVal' => true]
['myVal' => false]
['myVal' => 'some value']
['my val' => 'some value']
// etc
```

***Note:*** You may specify non-scalar values with this library (eg. nested arrays), however they aren't dealt with in any special way. They are currently treated like scalar values.

### Validation

If you want to validate the given values you can add a callback closure. Each value that is picked will be passed to your callback to check that it's valid. If it returns a false-y value, an exception will be raised.

``` php
$callback = function (string $name, $value, bool $wasExpected): bool {
    return (is_bool($value)); // ensure the value is ok
};
// an exception will be raised because sendEmails is a string ("yes")
Options::validator($callback)->defaults('sendEmails sendSms !sendSnail')->resolve('sendEmails=yes');
```

### Chaining

The methods below may be chained together, and any of them can be called statically to instantiate an Options object:

``` php
$options = Options::allowUnexpected()->validator($callback)->defaults($defaults)->addDefaults($extraDefaults)->resolve($customValues); // chainable
$results = $options->all();
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.

## Treeware

You're free to use this package, but if it makes it to your production environment please plant or buy a tree for the world.

It's now common knowledge that one of the best tools to tackle the climate crisis and keep our temperatures from rising above 1.5C is to <a href="https://www.bbc.co.uk/news/science-environment-48870920">plant trees</a>. If you support this package and contribute to the Treeware forest you'll be creating employment for local families and restoring wildlife habitats.

You can buy trees here [offset.earth/treeware](https://offset.earth/treeware?gift-trees)

Read more about Treeware at [treeware.earth](http://treeware.earth)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Code of conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.

## Credits

- [Tim Chandler](https://github.com/code-distortion)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
