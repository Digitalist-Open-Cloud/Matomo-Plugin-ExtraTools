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
use Piwik\Option;

/**
 * The versions of Matomo and its plugins that a database was last updated to. Matomo keeps
 * them as options named "version_<component>" and runs the update scripts from that version on.
 *
 * Installing into a database that is already in use records every plugin as installed at the
 * version of its code. The versions from before the install are therefore read first and put
 * back after, so that the updates in between are still found and run.
 */
class RecordedVersions
{
    /**
     * @return array<string, string> option name => version. Empty for a database without Matomo.
     */
    public function read(): array
    {
        try {
            $rows = Db::fetchAll(
                'SELECT option_name, option_value FROM `' . Common::prefixTable('option') . "` WHERE option_name LIKE 'version\\_%'"
            );
        } catch (\Exception $e) {
            // The table does not exist yet.
            return [];
        }

        return array_column($rows, 'option_value', 'option_name');
    }

    /**
     * Puts back the versions that have changed since they were read. A component that has been
     * uninstalled since is left alone.
     *
     * @param array<string, string> $versions as returned by read()
     * @return string[] the components whose version was put back
     */
    public function restore(array $versions): array
    {
        $restored = [];
        foreach ($versions as $name => $version) {
            $current = Option::get($name);
            if ($current !== false && $current !== $version) {
                Option::set($name, $version, 1);
                $restored[] = substr($name, strlen('version_'));
            }
        }

        return $restored;
    }
}
