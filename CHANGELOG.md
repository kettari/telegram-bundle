# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
  - Add "default" notification which is on by default for all and can't be switched off.
  - Add external user name formatting for /register command.
  - Introduce /version command.
  - Add "EditMessageReplyMarkup" object to onRequestSent in AuditSubscriber to resolve Chat.

## [1.2.0] - 2017-07-21
### Changed
  - If user on `/register` sends numbers (usually typed phone), give him
  friendly clue to try again with special button.

## [1.1.0] - 2017-07-14
### Changed
  - Refactored event system. Now the bundle generates lots of events
  using event management system of the Symfony, see README.md for details.

## [1.0.0-RC1] - 2017-04-20
First release.