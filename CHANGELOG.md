# Changelog

## [1.1.0] - 2026-04-xx

### Added
- Added built-in Symfony Validator based parameter types for common input formats.
- Added automatic registration of Symfony parameter types during bootstrap.
- Added `scenario:make:parameter` command to generate custom Symfony parameter types.
- Added `scenario:parameter` command to list available built-in and registered parameter types.

### Changed
- Renamed `scenario:make` to `scenario:make:scenario`.
- Updated generator commands to separate scenario and parameter type creation.

### Notes
- Requires Scenario Core ^1.1.

## [1.0.1] - 2026-04-12

### Added
- added logo and badges and minor corrections in docs

## [1.0.0] - 2026-04-06

### Added
- Initial Symfony integration for Scenario Core.
- Integration into Symfony Console, providing installer and commands for managing, executing, and debugging scenarios.
- Integration with Console, Messenger, Events, and Doctrine.

### Stability
- Cross-platform support (Linux, Windows).

### Notes
- First stable release.
- Requires Scenario Core ^1.0.