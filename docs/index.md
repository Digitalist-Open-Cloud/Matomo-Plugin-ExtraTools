# Extra Tools (for Matomo)

Some extra cli commands to help with maintaining Matomo. Also providing an phpinfo page in the admin part.
Introducing new console commands:

* `archive:list`
* `config:get`
* `database:backup`
* `database:create`
* `database:drop`
* `database:import`
* `logger:delete`
* `logger:show`
* `matomo:install`
* `segment:admin`
* `segment:list`
* `site:add`
* `site:delete`
* `site:list`
* `site:url`
* `visits:get`
* `customdimensions:configure-new-dimension`

## Background

The main reason to doing this plugin was to get automatic installs to work with Matomo, including automatic updates -
and version controlled deliveries with configuration in json or yaml.

## Known bugs
Adding a site as part of `matomo:install` is currently broken, but you could just after the command run the `site:add` command:
```
./console site:add --name=Foo --urls=https://foo.bar
```

## Dependencies
Sine version 4.1.0-beta1 we are dependent on PHP 8.1

### On host:
* mysql-client or mariadb-client (for database tasks)
* PHP json extension

### In composer.json (Matomo root):

From version 4.1.0-beta1:
* `composer require symfony/yaml:~2.6.0` (moves it from dev)
* `composer require symfony/process:^5.4`

Earlier versions:
* `composer require symfony/yaml:~2.6.0` (moves it from dev)
* `composer require symfony/process:^3.4`

## Install

Git clone the plugin into your plugins folder:
```
git clone https://github.com/digitalist-se/extratools.git ExtraTools
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
Or add it manually to config.ini.php:
```
[ExtraTools]
db_backup_path = "/var/www/html/tmp"
```

## Commands

### `archive:list`
Gets al list of ongoing or scheduled core archivers, if such exist.

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

### `logger:delete`
Removes logging entries from the DB, that is the internal logging in Matomo, not visits on sites.

### `logger:show`
Show logging and query entries of logs from the database, output could be exported to CSV.

### `matomo:install`
To use matomo:install, you need ExtraTools to always be enabled, add `always_load_commands_from_plugin=ExtraTools` to `common.config.ini.php`.

Here is how we do it in ad docker image build:

```bash
    echo "[General]" > /var/www/html/config/common.config.ini.php; \
    echo "always_load_commands_from_plugin=ExtraTools" >> /var/www/html/config/common.config.ini.php; \
```

Installs Matamo. Wipes the current installation - as default it uses settings in
your config.ini.php file - but all values could be overridden with arguments or
environment variables.

If you have a license for Matomo Premium plugins, set the environment variable `MATOMO_LICENSE` with the correct
license token. The environment variable is set as a normal environment variable, in shell using export, in a
docker-compose file, the environment array etc. If the variable is set, Matomo will have the license key set on install.

### `segment:admin`

Administration of segments, only options right now is to delete or activate a segment, a deleted segment could later be activated again.

### `segment:list`

List all segments, with ID, definition, date created and latest updated.

### `site:add`

Adds a new site to track.

### `site:delete`

Deletes a site with ID provided.

### `site:list`

List sites, with the optional format argument - supported output is text(default), json and yaml.

### `site:url`

Adds one or more URLs to a site.

### `visits:get`

Get all archived visits, for one site or all. For a segment or all segments, for today, or another day etc.

### `customdimensions:configure-new-dimension`

Configure a new custom dimension. BETA.

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
Supported default environment variables from the official Matomo docker container:

```bash
MATOMO_DATABASE_HOST
MATOMO_DATABASE_PORT
MATOMO_DATABASE_TABLES_PREFIX
MATOMO_DATABASE_USERNAME
MATOMO_DATABASE_PASSWORD
MATOMO_DATABASE_DBNAME
MATOMO_DATABASE_ADAPTER
```

These could be overridden with (historical reasons):

```bash
MATOMO_DB_HOST
MATOMO_DB_PREFIX
MATOMO_DB_USERNAME
MATOMO_DB_PASSWORD
MATOMO_DB_NAME
```

Other environment variables:

```bash
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

Then follow one of the Examples below.

#### Example install 1 (recommended)
```
console matomo:install --install-file=install.json
```

#### Example install 2
```
console matomo:install --db-username=myuser --db-pass=password \
  --db-host=localhost --db-port=3306 --db-name=matomo --first-site-name=Foo \
  --first-site-url=https//foo.bar --first-user='Mr Foo Bar' \
  --first-user-email=foo@bar.com --first-user-pass=secret
```
#### Example install 3
Using environment variables, docker-compose.yml example.
```
environment:
      - MATOMO_DB_USERNAME=myuser
      - MATOMO_DB_PASSWORD=secret
      - MATOMO_DB_HOST=mysql
      - MATOMO_DB_PORT=3306
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

## CAUTION!
* `matamo:install` wipes your current installation.
* `database:drop` - as it says - drops the entire db, make a backup first if you
want to save you data, and check if it's ok.
* `database:import` - writes over your current database.
* `site:delete` - really deletes a site you have setup in Matomo.

This plugin comes with **no** guarantees. But it's free and open source.
So, let's make it better!

## Version supported
This is tested from version 3.8.1, and should work with the latest stable.

## Thank you!
This plugin is based on work done by [Ben Evans](https://github.com/nebev) in
https://github.com/nebev/piwik-cli-setup, and also reusing code in Matomo
core.
