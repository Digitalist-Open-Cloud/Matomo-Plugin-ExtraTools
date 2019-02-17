# Matomo Extra Tools

Some extra cli commands to help with maintaining Matomo. 
Introducing three new console commands:
* `config:get`
* `database:backup`
* `database:create`
* `database:drop`
* `database:import`
* `matomo:install`
* `matomo:requirements`


## Dependencies
### On host:
* mysql-client (for database tasks)

### In composer.json (Matomo root):

* `composer require symfony/yaml:~2.6.0` (moves it from dev)
* `composer require symfony/process:^3.4`

optional (upgrade console):
* `composer require symfony/console:^3.4`

## Commands

### config:get
Gets a section config.

### database:backup
Backups the db. 

### database:drop
Drops the db defined i config.ini.php - backup first if needed.

### database:import
Imports database dump to database defined in config.ini.php.

### matomo:install
Not implemented yet

### matomo:requirements
Check that all requirements, mandatory and optional, are in place.
Normally throws a notice for mod pagespeed check.

## Config
To set a db backup path, use the console (use the path you desire):
```
./console config:set 'MatomoExtraTools.db_backup_path="/var/www/html/tmp"'
```
Or add it to config.ini.php:
```
[MatomoExtraTools]
db_backup_path = "/var/www/html/tmp"

```

## CAUTION!
`matamo:install` wipes your current install if you use the `--new` argument.
`database:drop` - as it says - drops the entire db, make a backup first if you 
want to save you data, and check if it's ok.
`database:import` - writes over your current database.

This plugin comes with **no** guarantees. But it's free and open source. 
So, let's make it better!
