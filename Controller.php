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
use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;

/**
 *
 */
class Controller extends ControllerAdmin
{
    public function index()
    {
        Piwik::checkUserHasSomeViewAccess();
        return $this->renderTemplate('index');
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
        Piwik::checkUserHasSuperUserAccess();
        $api = new API();
        // Get phpinfo
        $info = $api->getPhpInfo();
        return $this->renderTemplate('phpinfo', array(
            'info' => $info
        ));
    }
    public function invalidatedarchives()
    {
        // The list covers every website.
        Piwik::checkUserHasSuperUserAccess();
        $result = [];
        $list = new Archivers();
        foreach ($list->getAllInvalidations() as $out) {
            $out['name'] = $list->getLabel($out['name']);
            $out['period'] = array_flip(Piwik::$idPeriods)[$out['period']] ?? $out['period'];

            $result[] = $out;
        }
        return $this->renderTemplate('invalidatedarchives', array(
            'archivers' => $result
        ));
    }
}
