<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Piwik\Common;
use Piwik\Db;

class Archivers
{
    private static $rawPrefix = 'archive_invalidations';


    protected function getTable()
    {
        return Common::prefixTable(self::$rawPrefix);
    }


    /**
     * Returns all stored segments that haven't been deleted. Ignores the site the segments are enabled
     * for and whether to auto archive or not.
     *
     * @return array
     */
    public function getAllInvalidations()
    {
        $sql = "SELECT * FROM " . $this->getTable() . " ORDER BY `ts_invalidated`";

        $invalidations = $this->getDb()->fetchAll($sql);
        if (isset($invalidations)) {
            return $invalidations;
        } else {
            return false;
        }
    }
    private function getDb()
    {
        return Db::get();
    }
}
