<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\Config;
use Piwik\Plugin\Manager;
use Piwik\Plugins\ExtraTools\Lib\InstalledPlugins;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;

/**
 * @group ExtraTools
 * @group Plugins
 */
class InstalledPluginsTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // A database that is at an older version of the plugin, and a config file that is new.
        Updater::recordComponentSuccessfullyUpdated('Goals', '1.0.0');
        $this->setInstalledInConfig(array_diff($this->getInstalledInConfig(), ['Goals']));
    }

    public function testWithoutItMatomoRecordsThePluginAtTheVersionOfItsCode()
    {
        Manager::getInstance()->installLoadedPlugins();

        $this->assertNotSame('1.0.0', (new Updater())->getCurrentComponentVersion('Goals'));
    }

    public function testAPluginOfTheDatabaseKeepsItsVersionSoThatItsUpdatesAreFound()
    {
        $added = (new InstalledPlugins())->addToConfig(Config::getInstance());
        Manager::getInstance()->installLoadedPlugins();

        $this->assertContains('Goals', $added);
        $this->assertSame('1.0.0', (new Updater())->getCurrentComponentVersion('Goals'));
        $this->assertArrayHasKey('Goals', (new Updater())->getComponentsWithNewVersion(['Goals' => '9.9.9']));
    }

    public function testOnlyPluginsAreTakenFromTheDatabase()
    {
        Updater::recordComponentSuccessfullyUpdated('NoSuchPlugin', '1.0.0');
        Updater::recordComponentSuccessfullyUpdated('log_visit.some_column', 'INT');

        $plugins = (new InstalledPlugins())->getFromDatabase();

        $this->assertContains('Goals', $plugins);
        $this->assertNotContains('core', $plugins);
        $this->assertNotContains('NoSuchPlugin', $plugins);
        $this->assertNotContains('log_visit.some_column', $plugins);
    }

    public function testNothingIsAddedWhenTheConfigFileAlreadyKnowsThePlugins()
    {
        $installedPlugins = new InstalledPlugins();
        $installedPlugins->addToConfig(Config::getInstance());

        $this->assertSame([], $installedPlugins->addToConfig(Config::getInstance()));
    }

    /**
     * @return string[]
     */
    private function getInstalledInConfig(): array
    {
        return Config::getInstance()->PluginsInstalled['PluginsInstalled'] ?? [];
    }

    private function setInstalledInConfig(array $plugins): void
    {
        $section = Config::getInstance()->PluginsInstalled;
        $section['PluginsInstalled'] = array_values($plugins);
        Config::getInstance()->PluginsInstalled = $section;
    }
}
