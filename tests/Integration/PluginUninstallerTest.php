<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\Common;
use Piwik\DbHelper;
use Piwik\Plugin\Manager;
use Piwik\Plugins\ExtraTools\Lib\PluginUninstaller;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group ExtraTools
 * @group Plugins
 */
class PluginUninstallerTest extends IntegrationTestCase
{
    public function testAPluginIsTurnedOffAndItsTablesRemovedButItsFilesStay()
    {
        $table = Common::prefixTable('user_dashboard');
        $this->assertTrue(Manager::getInstance()->isPluginActivated('Dashboard'));
        $this->assertContains($table, DbHelper::getTablesInstalled(true));

        (new PluginUninstaller())->uninstall('Dashboard');

        $this->assertFalse(Manager::getInstance()->isPluginActivated('Dashboard'));
        $this->assertFalse(Manager::getInstance()->isPluginLoaded('Dashboard'));
        $this->assertNotContains($table, DbHelper::getTablesInstalled(true));
        $this->assertFileExists(PIWIK_INCLUDE_PATH . '/plugins/Dashboard/Dashboard.php');
    }
}
