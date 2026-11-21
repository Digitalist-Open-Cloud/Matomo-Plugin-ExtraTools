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

use Piwik\Columns\Dimension;
use Piwik\Filesystem;
use Piwik\Plugin;
use Piwik\Plugin\Dimension\ActionDimension;
use Piwik\Plugin\Dimension\ConversionDimension;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugin\Manager;
use Piwik\Settings\Storage\Backend\MeasurableSettingsTable;
use Piwik\Settings\Storage\Backend\PluginSettingsTable;
use Piwik\Updater;

/**
 * Uninstalls a plugin like Matomo does, except that the files of the plugin stay where they
 * are. An install file can so turn off plugins that come with Matomo or with an image.
 */
class PluginUninstaller
{
    public function uninstall(string $pluginName): void
    {
        $manager = Manager::getInstance();
        $manager->deactivatePlugin($pluginName);

        PluginSettingsTable::removeAllSettingsForPlugin($pluginName);
        MeasurableSettingsTable::removeAllSettingsForPlugin($pluginName);

        $plugin = $manager->loadPlugin($pluginName);
        if ($plugin !== null) {
            $plugin->uninstall();
            $this->uninstallDimensions($plugin);
            $manager->unloadPlugin($plugin);
        }

        (new Updater())->markComponentSuccessfullyUninstalled($pluginName);
        Filesystem::deleteAllCacheOnUpdate($pluginName);
    }

    /**
     * Removes the columns the plugin added to the log tables, unless another plugin uses them.
     */
    private function uninstallDimensions(Plugin $plugin): void
    {
        $kinds = [
            [VisitDimension::INSTALLER_PREFIX, VisitDimension::getDimensions($plugin), VisitDimension::getAllDimensions()],
            [ActionDimension::INSTALLER_PREFIX, ActionDimension::getDimensions($plugin), ActionDimension::getAllDimensions()],
            [ConversionDimension::INSTALLER_PREFIX, ConversionDimension::getDimensions($plugin), ConversionDimension::getAllDimensions()],
        ];

        foreach ($kinds as [$prefix, $dimensions, $allDimensions]) {
            foreach ($dimensions as $dimension) {
                if (!$this->isColumnOfAnotherPlugin($dimension, $allDimensions)) {
                    $dimension->uninstall();
                    (new Updater())->markComponentSuccessfullyUninstalled($prefix . $dimension->getColumnName());
                }
            }
        }
    }

    /**
     * @param Dimension[] $allDimensions
     */
    private function isColumnOfAnotherPlugin(Dimension $dimension, array $allDimensions): bool
    {
        foreach ($allDimensions as $other) {
            if (
                $other->getColumnName() === $dimension->getColumnName()
                && $other->hasColumnType()
                && $other->getModule() !== $dimension->getModule()
            ) {
                return true;
            }
        }

        return false;
    }
}
