<?php

namespace Piwik\Plugins\ExtraTools;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::hasUserSuperUserAccess()) {
            $menu->addDiagnosticItem('ExtraTools_PhpInfo', $this->urlForDefaultAction($orderId = 50));
        }
    }
}
