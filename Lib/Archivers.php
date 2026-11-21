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
     * Tells what an invalidation is for, from its name: "done", followed by the hash of a
     * segment if it is for one, and by "." and a plugin if it is for one plugin only.
     *
     * @return string eg, "All visits", "Returning visitors" or "Returning visitors (Goals)"
     */
    public function getLabel(string $name): string
    {
        if (!preg_match('/^done([0-9a-f]{32})?(?:\.(.+))?$/', $name, $parts)) {
            return $name;
        }

        $hash = $parts[1] ?? '';
        $label = 'All visits';
        if ($hash !== '') {
            // The segment may have been deleted since.
            $label = $this->getSegmentName($hash) ?: $hash;
        }

        return empty($parts[2]) ? $label : "$label ({$parts[2]})";
    }

    /**
     * @return string|false the name of the segment, or false if there is none with the hash
     */
    private function getSegmentName(string $hash)
    {
        try {
            return Db::get()->fetchOne('SELECT `name` FROM ' . Common::prefixTable('segment') . ' WHERE `hash` = ?', [$hash]);
        } catch (\Exception $e) {
            return false;
        }
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
