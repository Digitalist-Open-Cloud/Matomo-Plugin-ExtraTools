<?php

namespace Piwik\Plugins\ExtraTools;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::hasUserSuperUserAccess()) {
            $menu->addDiagnosticItem('ExtraTools_PhpInfo', $this->urlForAction('index'), $order = 50);
        }

        if (Piwik::hasUserSuperUserAccess()) {
            $menu->addDiagnosticItem(
                'ExtraTools_Invalidations',
                $this->urlForAction('invalidatedarchives'),
                $order = 50
            );
        }
    }
}
