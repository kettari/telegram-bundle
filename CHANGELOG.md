# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
  - [telegram-bundle-56](https://github.com/kettari/telegram-bundle/issues/56) Create kaula-telegram-bundle and move KYS dependencies to it. Purpose is
  to make current bundle useful for people outside Kaula Yoga School. I hope someday I will call it "Telegram Bot framework" :D
  - [telegram-bundle-59](https://github.com/kettari/telegram-bundle/issues/59) Add multilingual support. For now, it is full of Russian language :)
  - [telegram-bundle-51](https://github.com/kettari/telegram-bundle/issues/51) Add audit for different types of Message (photos, contacts, etc.).
  - [telegram-bundle-50](https://github.com/kettari/telegram-bundle/issues/50) Add "default" notification which is "on" by default for all and can't be switched off.
  - [telegram-bundle-48](https://github.com/kettari/telegram-bundle/issues/48) Introduce /version command.
  - [telegram-bundle-43](https://github.com/kettari/telegram-bundle/issues/43) Add "EditMessageReplyMarkup" object to onRequestSent in AuditSubscriber to resolve Chat.

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