# Extra Tools (for Matomo)

Some extra cli commands to help with maintaining Matomo. 
Introducing new console commands:

* `config:get`
* `database:backup`
* `database:create`
* `database:drop`
* `database:import`
* `matomo:install`
* `matomo:requirements` (does not work right now)
* `site:add`
* `site:list`

## Background

The main reason to doing this plugin was to get automatic installs to work with Matomo, including automatic updates -  and version controlled deliveries with configuration in json or yaml. 

## Plan

* Get installation to work with a *.json file with all the settings that should be installed (partly done)
* Get updates done with a *.json (not started)
* Add support for yaml besides json for install and updates.
* Add PHPUnit tests to cover at least 70% (goal for stable release is 100%)

## Dependencies

### On host:
* mysql-client (for database tasks)

### In composer.json (Matomo root):

* `composer require symfony/yaml:~2.6.0` (moves it from dev)
* `composer require symfony/process:^3.4`

## Install

Git clone the plugin into your plugins folder:
```
git clone https://github.com/nodeone/extratools.git ExtraTools
```

## Config
Activate ExtraTools - in UI, or better - in the console:
``` 
console plugin:activate ExtraTools
```

Set up a db backup path, use the console (use the path you desire):
```
./console config:set 'ExtraTools.db_backup_path="/var/www/html/tmp"'
```
Or add it manualy to config.ini.php:
```
[ExtraTools]
db_backup_path = "/var/www/html/tmp"

```


## Commands

### `config:get`
Gets a section config.
@todo - make this more like config:set - so you have more options.

### `database:backup`
Backups the db. 

### `database:create`
Creates the db defined i config.ini.php.
Adding the --force flag stops the command for asking questions.

### `database:drop`
Drops the db defined i config.ini.php - backup first if needed.
Adding the --force flag stops the command for asking questions.

### `database:import`
Imports database dump to database defined in config.ini.php, so if
you already have a installation - it overwrites it.

### `matomo:install`
Installs Matamo. Wipes the current installation - as default it uses settings in 
your config.ini.php file - but all values could be overriden with arguments or
environment variables.

If you have a license for Matomo Premium plugins, set the environment variable `MATOMO_LICENSE` with the correct license token. The environment variable is set as a normal environment variable, in shell using export, in a docker-compose file, the environment array etc. If the variable is set, Matomo will have the license key set on install.

### `site:add`

Adds a new site to track.

### `site:list`

List sites, with the optional format argument - supported output is text(default), json and yaml.

#### Requirements

Matomo needs a MySQL/MariaDB host, with a user setup that is allowed to drop 
that db.
The first user is created as a super user and it is need to have one to 
set up Matomo. If you do not add values in environment variables or options to 
matomo:install command, it will use the defaults for the user - so important 
that you change that users password after install.
Matomo also creates a first site to track, this also has default values that
you could override with environment variables or options.

You could also use a json-file for configuration - like all the above 
mentioned - and for installing plugins. An example json-file could be found in 
the docs folder for this plugin.

#### Environment variables
```bash
MATOMO_DB_USERNAME
MATOMO_DB_PASSWORD
MATOMO_DB_HOST
MATOMO_DB_NAME

MATOMO_FIRST_USER_NAME
MATOMO_FIRST_USER_EMAIL
MATOMO_FIRST_USER_PASSWORD

MATOMO_FIRST_SITE_NAME
MATOMO_FIRST_SITE_URL

MATOMO_LOG_TIMESTAMP (1)
```


#### Installation preparation
If you have a config.ini.php in the config dir - delete it.
Run:

```bash
console plugin:activate ExtraTools

```

Then follow one of the Examples below:


#### Example install 1 (recomended)
``` 
console matom-install --install-file=install.json
```

#### Example install 2
```
console matomo:install --db-username=myuser --db-pass=password \
  --db-host=localhost --db-name=matomo --first-site-name=Foo \
  --first-site-url=https//foo.bar --first-user='Mr Foo Bar' \
  --first-user-email= foo@bar.com --first-user-pass=secret
```
#### Example install 3
Using environment variables, docker-compose.yml example.
```
environment:
      - MATOMO_DB_USERNAME=myuser
      - MATOMO_DB_PASSWORD=secret
      - MATOMO_DB_HOST=mysql
      - MATOMO_DB_NAME=matomo
      - MATOMO_FIRST_USER_NAME=Mr Foo Bar
      - MATOMO_FIRST_USER_EMAIL=foo@bar.com
      - MATOMO_FIRST_USER_PASSWORD=secret
      - MATOMO_FIRST_SITE_NAME=Foo
      - MATOMO_FIRST_SITE_URL=https://foo.bar
```

#### Order of values
Highest number = takes over. If you have you mysql server settings in environment 
variables and provide the option --db-username=myuser, the latter is used for the
db username.

1) config.ini.php (created when you install the first time)
2) Environment variable
3) Option (matomo:install --db-username=myuser)
4) File overrides (matom-install --install-file=install.json)

### matomo:requirements
Check that all requirements, mandatory and optional, are in place.
Normally throws a notice for mod_pagespeed check.
@todo: Look into what needs to be done in core for the mod_pagespeed check.

## CAUTION!
`matamo:install` wipes your current installation.
`database:drop` - as it says - drops the entire db, make a backup first if you 
want to save you data, and check if it's ok.
`database:import` - writes over your current database.

This plugin comes with **no** guarantees. But it's free and open source. 
So, let's make it better!

## Version supported
This is tested from version 3.8.1, and should work with the latest stable 
(as of writing, that is 3.9.1)

## Thank you!
This plugin is based on work done by [Ben Evans](https://github.com/nebev) in 
https://github.com/nebev/piwik-cli-setup, and also reusing code in Matomo
core.
