# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [3.0.1] - 2025-04-03
### Fixed
- Fixed Selectel API exception handling.

## [3.0.0] - 2025-04-03
- Upgrade to league/flysystem 3.x.
- Added method "directoryExists"

## [2.0.0] - 2025-04-03
- Upgrade to league/flysystem 2.5.x.
- Support for PHP 5.6 has been removed. PHP version 8.1 or higher is now required.

## [Unreleased]

## [1.2.0] - 2017-09-27
### Added
- Added support for Laravel 5.5.'s auto-discovery feature (thanks @reg2005).

## [1.1.2] - 2017-07-26
### Added
- Add `mimetype` field to transformed files array, allowing to use Flysystem's `getMimetype` method.

## [1.1.1] - 2017-04-18
### Changed
- Perform API authentication in SelectelServiceProvider.

## [1.1.0] - 2017-04-18
### Added
- New configuration option `container_url`. This will help you to retrieve file URLs while using custom CDN domain;
- `SelectelAdapter::getUrl` method to retrieve full URL to given file/directory path. This will allow Laravel to use File URLs just like with `s3` adapter.

## [1.0.1] - 2017-03-11
### Added
- Built-in Service Provider for Laravel Framework;
- Information about Flysystem methods that are not supported by this adapter.

## [1.0.0] - 2017-03-11
First release.

### Added
- Laravel Integration docs.

### Fixed
- Directories are marked as `dir` in content listings;
- Fixed issue with single file retrieving;
- Fixed issue with file/directory sizes detection.

### Removed
- Visibility support.

## [0.9.0] - 2017-03-11
Initial release.

[Unreleased]: https://github.com/ArgentCrusade/flysystem-selectel/compare/1.2.0...HEAD
[1.2.0]: https://github.com/ArgentCrusade/flysystem-selectel/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/ArgentCrusade/flysystem-selectel/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/ArgentCrusade/flysystem-selectel/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/ArgentCrusade/flysystem-selectel/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/ArgentCrusade/flysystem-selectel/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/ArgentCrusade/flysystem-selectel/compare/0.9.0...1.0.0
