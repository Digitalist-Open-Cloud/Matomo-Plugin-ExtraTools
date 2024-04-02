<?php

namespace Piwik\Plugins\ExtraTools;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::isUserHasSomeAdminAccess()) {
            $menu->registerMenuIcon('ExtraTools', 'icon-rocket');
            $menu->addItem('ExtraTools', null, $this->urlForAction('index'), $order = 50);
            $menu->addItem('ExtraTools', 'ExtraTools_ExtraTools', $this->urlForAction('index'), $order = 51);
            $menu->addItem('ExtraTools', 'ExtraTools_Documentation', $this->urlForAction('docs'), $order = 52);
            $menu->addItem('ExtraTools', 'ExtraTools_PhpInfo', $this->urlForAction('phpinfo'), $order = 53);
            $menu->addItem(
                'ExtraTools',
                'ExtraTools_Invalidations',
                $this->urlForAction('invalidatedarchives'),
                $order = 54
            );
        }
    }
}
