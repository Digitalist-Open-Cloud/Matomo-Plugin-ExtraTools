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
use Piwik\Config;
use Piwik\Db;
use Piwik\Plugin\Manager;

/**
 * The plugins that a database already has installed.
 *
 * Matomo decides from the config file whether a plugin is installed. When the config file is
 * new but the database is not, as after an update in a container, every plugin looks new: it
 * is installed again and recorded at the version of its code, and the updates since the version
 * the database is really at are never run. The database knows better: it has a
 * "version_<plugin>" option for each plugin that was installed.
 */
class InstalledPlugins
{
    /**
     * @return string[] the plugins the database has a version for and whose files exist.
     *                  Empty for a database without Matomo.
     */
    public function getFromDatabase(): array
    {
        try {
            $names = Db::fetchAll(
                'SELECT option_name FROM `' . Common::prefixTable('option') . "` WHERE option_name LIKE 'version\\_%'"
            );
        } catch (\Exception $e) {
            // The table does not exist yet.
            return [];
        }

        $manager = Manager::getInstance();
        $plugins = [];
        foreach (array_column($names, 'option_name') as $name) {
            // The other versions are of Matomo itself and of columns, named "table.column".
            $plugin = substr($name, strlen('version_'));
            if ($manager->isPluginInFilesystem($plugin)) {
                $plugins[] = $plugin;
            }
        }

        return $plugins;
    }

    /**
     * Tells Matomo, for this run, that the plugins the database has installed are installed.
     *
     * @return string[] the plugins the config file did not have as installed
     */
    public function addToConfig(Config $config): array
    {
        $section = $config->PluginsInstalled;
        $known = $section['PluginsInstalled'] ?? [];
        $added = array_values(array_diff($this->getFromDatabase(), $known));

        if ($added) {
            $section['PluginsInstalled'] = array_merge($known, $added);
            $config->PluginsInstalled = $section;
        }

        return $added;
    }
}
