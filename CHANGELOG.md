# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2017-07-21
### Added
  - [telegram-bundle-58](https://github.com/kettari/telegram-bundle/issues/58) Update `/start` command to understand deep linking with "register" payload.
  - [telegram-bundle-57](https://github.com/kettari/telegram-bundle/issues/57) Add hasRole() method to User entity.
### Changed
  - [telegram-bundle-49](https://github.com/kettari/telegram-bundle/issues/49) Add external user name formatting for /register command.
  - [telegram-bundle-53](https://github.com/kettari/telegram-bundle/issues/53) When user /register check if he sent not-a-contact but numbers and tell right tip (more friendly).

## [1.1.0] - 2017-07-14
### Changed
  - Refactored event system. Now the bundle generates lots of events
  using event management system of the Symfony, see README.md for details.

## [1.0.0-RC1] - 2017-04-20
First release.