# Changelog

All notable changes to `code-distortion/options` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



## [0.6.1] - 2025-01-28

### Fixed
- Allow `null` to be passed as an option. It is ignored instead of throwing an exception



## [0.6.0] - 2025-01-27

### Added
- Added support for PHP 7.0
- Added support for PHP 8.4
- Added `strict_types=1` to all files
- Improved the test suite
- Added the `amendOptions()` method
- Added the ability for the caller to choose if unexpected options (not present in the set of defaults) should be ignored or throw an exception
- Added the ability for the caller to choose if invalid options (checked by the `validator()` callback) should be ignored or throw an exception
- Updated the mechanism used to call the `validator()` callback so the callback can choose which parameters to use (from `$name`, `$value` and `$wasExpected`), and the order

### Changed
- Made the normal constructor publicly callable
- Renamed the `resolve()` method to `options()`
- Renamed the `addDefaults()` method to `amendDefaults()`
- Replaced the `allowUnexpected()` method with `restrictUnexpected()`
- Changed the unexpected option checking so it ignores unexpected options by default instead of throwing an exception
- Changed the validator checking so it ignores invalid options by default instead of throwing an exception
- Options returned by `all()` are now ordered in alphabetical order

### Removed
- Removed the `parse()` method
- Removed the `hasCustom()` method
- Removed the `getCustom()` method
- Removed the `hasDefault()` method
- Removed the `getDefault()` method
- Removed the `getDefaults()` method
- Removed the ability for `options()`, `amendOptions()`, `defaults()`, `amendDefaults()`, `restrictUnexpected()` and `validator()` to be called statically
- Removed `UndefinedMethodException` which was thrown if a method was called that didn't exist



## [0.5.8] - 2024-08-08

### Added
- Added support for PHP 8.3



## [0.5.7] - 2022-12-19

### Added
- Added support for PHP 8.2



## [0.5.6] - 2022-12-06

### Fixed
- Updated tests so they run again



## [0.5.5] - 2022-01-01

### Fixed
- Changed dependency list to refer to specific versions of PHP - to stop installation on platforms with future versions of PHP before being tested



## [0.5.4] - 2021-12-23

### Added
- Added support for PHP 8.1
- Added phpstan ^1.0 to dev dependencies



## [0.5.3] - 2021-02-21

### Added
- Added support for PHP 8
- Updated to PSR12



## [0.5.2] - 2020-03-08

### Added
- Updated dependencies



## [0.5.1] - 2020-01-29

### Changed
- Reviewed README.md
- added a base OptionsException class
- cleaned up doc-blocks



## [0.5.0] - 2020-01-26

### Added
- GitHub actions workflows file

### Changed
- Updated the code-of-conduct to https://www.contributor-covenant.org/version/2/0/code_of_conduct.html
- Added Treeware details
- Bumped dependencies



## [0.4.0] - 2019-11-13

### Added
- added hasDefault() and hasCustom() methods - to complement has()
- added getDefault() and getCustom() methods - to complement get()

### Changed (breaking)
- renamed value() method to get()

### Changed
- Added custom exceptions



## [0.3.0] - 2019-11-12

### Added
- all() method to retrieve all the resolved values
- value('x') method to retrieve a particular option's value
- has('x') method to check if a particular option exists
- Updates to documentation

### Changed
- Changed the resolve() method to be chainable instead of returning the resolved option values



## [0.2.0] - 2019-11-05

### Changed
- Added check to ignore null values



## [0.1.0] - 2019-11-04

### Added
- beta release
