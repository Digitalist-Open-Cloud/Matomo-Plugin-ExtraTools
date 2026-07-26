# Changelog

## [Unreleased]

## Added

- `extra:clear-external-cache` command to clear Matomo's cache entries from the external (Redis) cache backend without relying on `FLUSHDB`.

## [5.3.1] - 2026-07-26

## Fixed

- Fix for tagmanager

## [5.3.0] - 2026-07-25

## Fixed

- Rewrite of some parts of the plugin to reduce codebase and remove dead code.

## Added

- Tests together with Digitalist Open Cloud Helm chart for Matomo.

## [5.2.0] - 2026-07-25

### Added

- SSL/TLS support for the database commands (`database:backup`, `database:create`, `database:drop`, `database:import`) and `matomo:install`, using the `enable_ssl`, `ssl_ca`, `ssl_cert`, `ssl_key`, `ssl_ca_path`, `ssl_cipher` and `ssl_no_verify` keys from the `[database]` config, matching command line options and environment variables.

## [5.1.5] - 2025-07-25

### Changed

- [Email sending enabled after install](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-ExtraTools/pull/48), thanks @mbrodala
- [Ensure correct directory with composer/installers](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-ExtraTools/pull/53), thanks @mbrodala
- [Output "currency" in site:list command](https://github.com/Digitalist-Open-Cloud/Matomo-Plugin-ExtraTools/pull/52), thanks @mbrodala

## [5.1.4] - 2025-07-25

### Changed

- Use ControllerAdmin for admin links.

## [5.1.3] - 2025-02-21

### Changed

- `config:get` renamed to `extra:config:get`

## [5.1.2] - 2025-02-21

### Added

- Github actions for tests.

### Fixed

- Code formatting

## [5.1.1] - 2024-11-18

### Fixed

- Prevent duplicates via "site:add" by [mbrodala](https://github.com/mbrodala)

### Added

- License info on all files.

## [5.1.0] - 2024-10-28

### Added

- Support for collation and charset - as needed for Matomo 5.1.2

### Changed

- Drop and Create database functions.

## [5.0.8] - 2024-10-28

### Fixed

- Running "matomo:install" without "--force" fails: "getHelper can not be used" #41
- Missing tables after "matomo:install" #44

## [5.0.4] - 2024-08-30

### Added

- Marketplace cover

## [5.0.3] - 2024-04-08

### Removed

* Setting maintenance mode breaks, removing.

## [5.0.2] - 2024-04-08

### Added

* Extra Tools menu in Administration interface.
* Documentation page at Administration -> Extra Tools -> Documentation.

### Changed

* `phpinfo()` page moved to: Administration -> Extra Tools -> Phpinfo.
* Archive validations page moved to: Administration -> Extra Tools -> Invalidations.

### Removed

* Old changelog entries removed, to make it simpler to follow the new format. The old was also badly updated (sorry for that).
