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

namespace Piwik\Plugins\ExtraTools;

// Register the plugin's own Composer dependencies (e.g. symfony/process,
// symfony/yaml). Matomo boots its own vendor/autoload.php and does not load a
// plugin's vendor/ automatically, so we require it here if present.
$extraToolsAutoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($extraToolsAutoloader)) {
    require_once $extraToolsAutoloader;
}

class ExtraTools extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        );
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'ExtraTools_ExtraTools';
        $translationKeys[] = 'ExtraTools_Documentation';
        $translationKeys[] = 'ExtraTools_Invalidations';
        $translationKeys[] = 'ExtraTools_PhpInfo';
    }
}
