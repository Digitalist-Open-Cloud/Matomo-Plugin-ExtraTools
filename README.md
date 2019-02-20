# Matomo Extra Tools

Some extra cli commands to help with maintaining Matomo. 
Introducing new console commands:
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

### config:get
Gets a section config.
@todo - make this more like config:set - so you have more options.

### database:backup
Backups the db. 

### database:create
Creates the db defined i config.ini.php.
Adding the --force flag stops the command for asking questions.

### database:drop
Drops the db defined i config.ini.php - backup first if needed.
Adding the --force flag stops the command for asking questions.

### database:import
Imports database dump to database defined in config.ini.php, so if
you already have a installation - it overwrites it.

### matomo:install
Installs Matamo. Wipes the current installation - as default it uses settings in 
your config.ini.php file - but all values could be overriden with arguments or
environment variables.

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
teh docs folder for this plugin.

#### Environment variables
```
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

#### Example install 1
```
console matomo:install --db-username=myuser --db-pass=password \
  --db-host=localhost --db-name=matomo --first-site-name=Foo \
  --first-site-url=https//foo.bar --first-user='Mr Foo Bar' \
  --first-user-email= foo@bar.com --first-user-pass=secret
```
#### Example install 2
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

#### Example install 3
``` 
matom-install --install-file=install,json
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
`matamo:install` wipes your current install without asking for permission.
`database:drop` - as it says - drops the entire db, make a backup first if you 
want to save you data, and check if it's ok.
`database:import` - writes over your current database.

This plugin comes with **no** guarantees. But it's free and open source. 
So, let's make it better!
