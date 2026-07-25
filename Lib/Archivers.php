<?php

/**
 * The Extra Tools plugin for Matomo.
 *
 * Copyright (C) Digitalist Open Cloud <cloud@digitalist.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
     * @return mixed
     */
    public function getAllInvalidations()
    {
        $sql = "SELECT * FROM " . $this->getTable() . " ORDER BY `ts_invalidated`";

        return Db::get()->fetchAll($sql);
    }
}
