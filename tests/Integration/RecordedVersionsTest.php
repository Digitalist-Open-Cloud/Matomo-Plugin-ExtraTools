<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\Option;
use Piwik\Plugins\ExtraTools\Lib\RecordedVersions;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;

/**
 * @group ExtraTools
 * @group Plugins
 */
class RecordedVersionsTest extends IntegrationTestCase
{
    public function testAVersionThatAnInstallOverwritesIsPutBackSoThatItsUpdatesAreFound()
    {
        Updater::recordComponentSuccessfullyUpdated('Goals', '1.0.0');
        $versions = new RecordedVersions();
        $before = $versions->read();
        $this->assertSame('1.0.0', $before['version_Goals']);

        // What installing into a database in use does to every plugin.
        Updater::recordComponentSuccessfullyUpdated('Goals', '9.9.9');
        $this->assertArrayNotHasKey('Goals', (new Updater())->getComponentsWithNewVersion(['Goals' => '9.9.9']));

        $this->assertSame(['Goals'], $versions->restore($before));

        $this->assertSame('1.0.0', (new Updater())->getCurrentComponentVersion('Goals'));
        $this->assertArrayHasKey('Goals', (new Updater())->getComponentsWithNewVersion(['Goals' => '9.9.9']));
    }

    public function testComponentsThatWereAddedOrUninstalledSinceAreLeftAlone()
    {
        Updater::recordComponentSuccessfullyUpdated('Goals', '1.0.0');
        $versions = new RecordedVersions();
        $before = $versions->read();

        Option::delete('version_Goals');
        Updater::recordComponentSuccessfullyUpdated('BrandNew', '2.0.0');

        $this->assertSame([], $versions->restore($before));
        $this->assertFalse(Option::get('version_Goals'));
        $this->assertSame('2.0.0', Option::get('version_BrandNew'));
    }

    public function testNothingIsPutBackWhenNothingChanged()
    {
        $versions = new RecordedVersions();

        $this->assertSame([], $versions->restore($versions->read()));
    }
}
