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

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::isUserHasSomeAdminAccess()) {
            $menu->registerMenuIcon('ExtraTools_ExtraTools', 'icon-rocket');
            $menu->addItem('ExtraTools_ExtraTools', null, $this->urlForAction('index'), $order = 50);
            $menu->addItem('ExtraTools_ExtraTools', 'ExtraTools_ExtraTools', $this->urlForAction('index'), $order = 51);
            $menu->addItem('ExtraTools_ExtraTools', 'ExtraTools_Documentation', $this->urlForAction('docs'), $order = 52);
            if (Piwik::hasUserSuperUserAccess()) {
                $menu->addItem('ExtraTools_ExtraTools', 'ExtraTools_PhpInfo', $this->urlForAction('phpinfo'), $order = 53);
                $menu->addItem(
                    'ExtraTools_ExtraTools',
                    'ExtraTools_Invalidations',
                    $this->urlForAction('invalidatedarchives'),
                    $order = 54
                );
            }
        }
    }
}
