# Options

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/options.svg?style=flat-square)](https://packagist.org/packages/code-distortion/options)
![PHP Version](https://img.shields.io/badge/PHP-7.1%20to%208.3-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/code-distortion/options/run-tests.yml?branch=master&style=flat-square)](https://github.com/code-distortion/options/actions)
[![Buy The World a Tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/code-distortion/options)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)

***code-distortion/options*** is a PHP library for managing options in a simple, flexible and expressive way.

``` php
use CodeDistortion\Options\Options;

$results = Options::parse('sendAlerts dailyLimit=123.45 !applyDailyLimit currency=AUD plan="Silver Plan"');
// [ 'sendAlerts' => true,
//   'dailyLimit' => 123.45,
//   'applyDailyLimit' => false,
//   'currency' => 'AUD',
//   'plan' => 'Silver Plan' ]

// when used programatically
$options = Options::resolve('sendEmails sendSms !sendSlack');
$value = $options->get('sendEmails'); // true
$value = $options->get('sendSlack'); // false
```



## Installation

Install the package via composer:

``` bash
composer require code-distortion/options
```



## Usage

The `parse()` method will take your string and break it down into separate options:

``` php
use CodeDistortion\Options\Options;

$results = Options::parse('sendEmails sendSms !sendSlack');
// [ 'sendEmails' => true,
//   'sendSms' => true,
//   'sendSlack' => false ]
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

You can specify values simply as arrays:

``` php
['myVal' => true]
['myVal' => false]
['myVal' => 'some value']
['my val' => 'some value']
// etc
```

***Note:*** You can specify non-scalar values with this library (eg. nested arrays), however they aren't dealt with in any special way. They are currently treated like scalar values.



### Using in your code

You can use an Options instance to handle values for you programmatically. The `resolve()` method will parse the input and return the Options object which you can then interrogate in your code:

``` php
$options = Options::resolve('sendEmails sendSms !sendSlack');
$has = $options->has('sendEmails'); // true
$has = $options->has('sendTweet');  // false
$value = $options->get('sendEmails'); // true
$value = $options->get('sendTweet');  // null
```



### Specifying default values

You can specify default fall-back values to use by calling `defaults()`:

``` php
// set the defaults for the first time (or replaces them completely)
$defaults = 'sendEmails sendSms !sendSlack';
$options = Options::defaults($defaults);

// add to the existing defaults (overriding where necessary)
$quietModeDefaults = '!sendSms';
$options->addDefaults($quietModeDefaults);

// retrieve the defaults back as an array
$options->getDefaults();
```



#### Resolving a set of options with defaults

To combine default values and custom values, use the `defaults()` method and then `resolve()`:

``` php
// combine default and custom values
$defaults = 'sendEmails sendSms !sendSlack';
$userPrefs = '!sendEmails sendSms';
$options = Options::defaults($defaults)->resolve($userPrefs);

// check if particular options exist
$has = $options->has('sendEmails'); // true
$has = $options->has('sendTweet');  // false
$has = $options->hasDefault('sendSlack'); // true
$has = $options->hasCustom('sendSlack');  // false

// retrieve individual values from $options
$value = $options->get('sendEmails'); // false
$value = $options->get('sendTweet');  // null
$value = $options->getDefault('sendEmails'); // true
$value = $options->getCustom('sendEmails');  // false

// get the results combined
$results = $options->all();
// [ 'sendEmails' => false,
//   'sendSms' => true,
//   'sendSlack' => false ]
```

***Note:*** If you specify default values, any values passed to `resolve()` that aren't present in the defaults will generate an exception unless `allowUnexpected()` is called before hand:

``` php
// InvalidOptionException: "The option "sendTweet" was not expected"
$options = Options::defaults('sendEmails sendSms !sendSlack')->resolve('sendTweet');

// the 'sendTweet' option is now allowed because allowUnexpected() was called
$options = Options::defaults('sendEmails sendSms !sendSlack')->allowUnexpected()->resolve('sendTweet');
```



### Validation

If you want to validate the given values you can pass a callback closure to `validator()`. Each value that is picked will be passed to your callback to check that it's valid. If it returns a false-y value, an exception will be raised.

``` php
$callback = function (string $name, $value, bool $wasExpected): bool {
    return (is_bool($value)); // ensure the value is a boolean
};

// InvalidOptionException: "The option "sendEmails" and/or it's value "yes" are not allowed"
Options::validator($callback)->defaults('sendEmails sendSms !sendSlack')->resolve('sendEmails=yes');
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

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth, it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.



## Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/code-distortion/options) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.



### Code of Conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.



### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.



## Credits

- [Tim Chandler](https://github.com/code-distortion)



## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
