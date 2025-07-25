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

use Piwik\Plugins\ExtraTools\Lib\Archivers;
use Piwik\Common;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;

/**
 *
 */
class Controller extends ControllerAdmin
{
    private static $rawPrefix = 'segment';

    protected function getTable()
    {
        return Common::prefixTable(self::$rawPrefix);
    }


    public function index()
    {
        Piwik::checkUserHasSomeViewAccess();
        $info = "<p>ExtraTools is an open source plugin for Matomo, developed and maintained by
          <a href='https://digitalist.cloud'>Digitalist Open Cloud</a> and the Matomo Community.
          ExtraTools adds a lot of functionality to your Matomo instance, focusing on
          automation in the backend, like installing, handling segments,
          sites and more.</p>
          <p>We are providing <a href='https://digitalist.cloud/tjanster/matomo'>professional services</a>,
          <a href='https://github.com/digitalist-se/MatomoPlugins'>open source and licensed plugins</a>.</p>";
        return $this->renderTemplate('index', array(
            'info' => $info
        ));
    }

    public function docs()
    {
        Piwik::checkUserHasSomeViewAccess();
        $info = "ExtraTools Docs";
        return $this->renderTemplate('docs', array(
            'info' => $info
        ));
    }


    public function phpinfo()
    {
        Piwik::checkUserHasSomeAdminAccess();
        $api = new API();
        // Get phpinfo
        $info = $api->getPhpInfo();
        return $this->renderTemplate('phpinfo', array(
            'info' => $info
        ));
    }
    public function invalidatedarchives()
    {
        Piwik::checkUserHasSomeAdminAccess();
        $result = [];
        $archivers = $this->getArchivers();
        foreach ($archivers as $out) {
            if ($out['name'] == 'done') {
                $out['name'] = 'All visits';
            } else {
                $hash = preg_replace('/^done/', '', $out['name']);
                $name = $this->getSegmentName($hash);
                $out['name'] = $name['name'];
            }
            switch ($out['period']) {
                case 1:
                    $out['period'] = 'day';
                    break;
                case 2:
                    $out['period'] = 'week';
                    break;
                case 3:
                    $out['period'] = 'month';
                    break;
                case 4:
                    $out['period'] = 'year';
                    break;
            }

            $result[] = $out;
        }
        return $this->renderTemplate('invalidatedarchives', array(
            'archivers' => $result
        ));
    }


    public function getArchivers()
    {
        $list = new Archivers();
        $out = $list->getAllInvalidations();
        return $out;
    }

    public function getSegmentName($hash)
    {
        try {
            $sql = "SELECT `name` FROM " . $this->getTable() . " WHERE `hash` = '" . $hash . "';";
            $name = $this->getDb()->fetchRow($sql);
            return $name;
        } catch (\Exception $e) {
            return false;
        }
    }
    private function getDb()
    {
        return Db::get();
    }
}
