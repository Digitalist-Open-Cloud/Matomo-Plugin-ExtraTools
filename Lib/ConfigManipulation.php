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

use Piwik\Plugins\CoreAdminHome\Commands\SetConfig\ConfigSettingManipulation;
use Piwik\Config as Config;

class ConfigManipulation
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function saveConfig($section, $key, $value)
    {
        $isSingleAssignment = !empty($section) && !empty($key) && $value !== false;
        $config = Config::getInstance();
        if ($isSingleAssignment) {
            $manipulation = new ConfigSettingManipulation($section, $key, $value);
            $manipulation->manipulate($config);
        }
        $config->forceSave();
    }
}
