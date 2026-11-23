<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\Config;
use Piwik\Plugins\ExtraTools\Lib\Install;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @group ExtraTools
 * @group Plugins
 */
class InstallTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // A database that is at an older version of the plugin, as when matomo:install runs over an
        // existing database, so the install has a migration to run.
        Updater::recordComponentSuccessfullyUpdated('Goals', '1.0.0');
        $this->setInstalledInConfig(array_merge($this->getInstalledInConfig(), ['Goals']));
    }

    public function testTheInstallRunsTheMigrationsAndPrintsThem()
    {
        $output = new BufferedOutput();
        $result = $this->updateComponents($output);

        $this->assertSame([], $result['errors']);
        $this->assertNotSame('1.0.0', (new Updater())->getCurrentComponentVersion('Goals'));

        $text = $output->fetch();
        $this->assertStringContainsString('Executing', $text);
        $this->assertMatchesRegularExpression('/Done\. \[\d+ \/ \d+\]/', $text);
    }

    public function testASilentInstallRunsTheMigrationsWithoutPrintingThem()
    {
        $output = new BufferedOutput();
        $result = $this->updateComponents($output, true);

        $this->assertSame([], $result['errors']);
        $this->assertNotSame('1.0.0', (new Updater())->getCurrentComponentVersion('Goals'));
        $this->assertStringNotContainsString('Executing', $output->fetch());
    }

    /**
     * @return array
     */
    private function updateComponents(BufferedOutput $output, $silent = false)
    {
        $install = new Install([], $output);
        $install->silent = $silent;

        $method = new \ReflectionMethod(Install::class, 'updateComponents');
        $method->setAccessible(true);
        $result = $method->invoke($install);

        $this->assertIsArray($result);
        return $result;
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
        $section['PluginsInstalled'] = array_values(array_unique($plugins));
        Config::getInstance()->PluginsInstalled = $section;
    }
}
