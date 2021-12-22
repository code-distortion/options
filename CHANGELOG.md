# Changelog

All notable changes to `code-distortion/options` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



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
