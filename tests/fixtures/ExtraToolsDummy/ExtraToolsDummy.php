<?php

namespace Piwik\Plugins\ExtraToolsDummy;

use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Plugin;

/**
 * Has a table from version 1.0.0 and adds a column to it in version 1.1.0, so that a test can
 * tell whether the update of a plugin was run.
 */
class ExtraToolsDummy extends Plugin
{
    public const TABLE = 'extratools_dummy';
    public const COLUMN_OF_UPDATE = 'added_by_update';

    public function install()
    {
        DbHelper::createTable(self::TABLE, '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)');
    }

    public function uninstall()
    {
        Db::dropTables([Common::prefixTable(self::TABLE)]);
    }
}
