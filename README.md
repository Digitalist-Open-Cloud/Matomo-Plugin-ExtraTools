# Matomo Extra Tools

Some extra cli commands to help with maintaining Matomo. 
We introduce three new console commands:
* `config:get`
* `database:backup`
* `matomo:install`

## CAUTION!
`matamo:install` wipes your currrent install.

## Config
To set a db backup path, use the console:
```
./console config:set 'MatomoExtraTools.db_backup_path="/var/www/html/tmp"'
```
Or add it to config.ini.php:
```
[MatomoExtraTools]
db_backup_path = "/var/www/html/tmp"

```
