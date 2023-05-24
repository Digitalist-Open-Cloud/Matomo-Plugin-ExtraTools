<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;
use Piwik\Tests\Fixtures\OneVisitorTwoVisits;

/**
 * @group ExtraTools
 * @group Plugins
 * @group Console
 */
class CommandsTest extends ConsoleCommandTestCase
{

    /**
     * @var ManySitesImportedLogs
     */
    public static $fixture;

    public function testSiteAddWithoutWebsiteNameShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:add',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("The website name can't be empty", $this->applicationTester->getDisplay());
    }
    public function testSiteAddWithWebsiteNameShouldSuceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:add',
            '--name' => 'Foo',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("Site Foo added", $this->applicationTester->getDisplay());
    }

    public function testSiteAddWithWebsiteNameAndUrlShouldSuceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:add',
            '--name' => 'Foo',
            '--urls' => 'https://foo.bar',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("Site Foo added", $this->applicationTester->getDisplay());
    }

    public function testSiteListShouldSuceedAndShowUrl()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:list',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("main-url: https://foo.bar", $this->applicationTester->getDisplay());
    }

    public function testSiteDeleteShouldFailWithoutId()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:delete',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You must provide an id for the site to delete", $this->applicationTester->getDisplay());
    }


    public function testSiteDeleteWithIdShouldSucceed()
    {
        $this->applicationTester->setInputs(['yes']);
        $code = $this->applicationTester->run(array(
            'command' => 'site:delete',
            '--id' => self::$fixture->idSite,
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("Are you really sure you would like to delete site", $this->applicationTester->getDisplay());
    }

    public function testSiteAddUrlWithoutIdShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:url',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You must provide an id for the site to add URL", $this->applicationTester->getDisplay());
    }

    public function testSiteAddUrlWithoutIdAndWithUrlShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:url',
            '--url' => 'https://foo.bar',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You must provide an id for the site to add URL", $this->applicationTester->getDisplay());
    }

    public function testSiteAddUrlWithoutUrlAndWithIdShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:url',
            '--id' => self::$fixture->idSite,
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You must provide an URL for the site", $this->applicationTester->getDisplay());
    }

    public function testSiteAddUrlWithNeededParametersShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:url',
            '--id' => self::$fixture->idSite,
            '--url' => 'https://foo.bar',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("URL https://foo.bar added for site 1", $this->applicationTester->getDisplay());
    }

    public function testSegmentAdminWithoutIdShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'segment:admin',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You need to provide a segment id", $this->applicationTester->getDisplay());
    }

    public function testSegmentAdminWithDeleteSegmentShouldFailBecauseItDoesNotExist()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'segment:admin',
            '--delete-segment' => '1',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You need to provide an existing segment id", $this->applicationTester->getDisplay());
    }
    public function testSegmentAdminWithActivateSegmentShouldFailBecauseItDoesNotExist()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'segment:admin',
            '--activate-segment' => '1',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You need to provide an existing segment id", $this->applicationTester->getDisplay());
    }

    public function testArchiveListShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'archive:list',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("No archivers ongoing or scheduled", $this->applicationTester->getDisplay());
    }

    /**
     * @group DatabaseBackup
     * @group Database
     */
    public function testDatabaseBackupWithoutBackupPathSetShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'database:backup',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("Value for backup-path is required", $this->applicationTester->getDisplay());
    }

    /**
     * @group DatabaseBackup
     * @group Database
     */
    public function testDatabaseBackupWitBackupPathSetShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'database:backup',
            '--backup-path' => '/tmp',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("Backup done", $this->applicationTester->getDisplay());
    }

    /**
     * @group DatabaseBackup
     * @group Database
     */
    public function testDatabaseBackupWitBackupPathAndBackupPrefixSetShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'database:backup',
            '--backup-path' => '/tmp',
            '--backup-prefix' => 'bar',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("/tmp/bar-", $this->applicationTester->getDisplay());
    }

    /**
     * @group DatabaseBackup
     * @group Database
     */
    public function testDatabaseBackupWitBackupPathAndNoPrefixSetShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'database:backup',
            '--backup-path' => '/tmp',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("/tmp/backup-", $this->applicationTester->getDisplay());
    }

    /**
     * @group ConfigGet
     */
    public function testConfigGetDatabaseShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'config:get',
            '--section' => 'database',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("dbname", $this->applicationTester->getDisplay());
    }

    /**
     * @group ConfigGet
     */
    public function testConfigGetFooBarShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'config:get',
            '--section' => 'foobar',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("Nothing found", $this->applicationTester->getDisplay());
    }

    /**
     * @group ConfigGet
     */
    public function testConfigGetNothingShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'config:get',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You must set either an argument or set options", $this->applicationTester->getDisplay());
    }

    /**
     * @group ConfigGet
     */
    public function testConfigGetFormatJsonShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'config:get',
            '--section' => 'database',
            '--format' => 'json',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase('{"host":"db"', $this->applicationTester->getDisplay());
    }

    /**
     * @group CustomDimensions
     */
    public function testCustomDimensionsWithoutNameShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'customdimensions:configure-new-dimension',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("A name is required", $this->applicationTester->getDisplay());
    }

    /**
     * @group CustomDimensions
     */
    public function testCustomDimensionsWithoutIdShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'customdimensions:configure-new-dimension',
            '--name' => 'foo',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("A site id (id) is required", $this->applicationTester->getDisplay());
    }

    /**
     * @group CustomDimensions
     */
    public function testCustomDimensionsWithoutScopeShouldFail()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'customdimensions:configure-new-dimension',
            '--name' => 'foo',
            '--id' => self::$fixture->idSite,
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("A scope (visit/action) is required", $this->applicationTester->getDisplay());
    }
    /**
     * @group CustomDimensions
     */
    public function testCustomDimensionsShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'customdimensions:configure-new-dimension',
            '--name' => 'foo',
            '--id' => self::$fixture->idSite,
            '--scope' => 'action',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
        $this->assertStringContainsStringIgnoringCase("Adding a new custom dimension", $this->applicationTester->getDisplay());
    }

    /**
     * @group DatabaseCreate
     * @group Database
     */
    public function testDatbaseCreateShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'database:create',
            '--force' => '',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
    }

    /**
     * @group Logger
     */
    public function testLoggerDeleteForceShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'logger:delete',
            '--force' => '',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
    }

    /**
     * @group Logger
     */
    public function testLoggerDeleteConfirmYesShouldSucceed()
    {
        $this->applicationTester->setInputs(['yes']);
        $code = $this->applicationTester->run(array(
            'command' => 'logger:delete',
            '-vvv' => true,
        ));
        $this->assertEquals(0, $code);
    }

    /**
     * @group Logger
     */
    public function testLoggerDeleteConfirmNoShouldFail()
    {
        $this->applicationTester->setInputs(['no']);
        $code = $this->applicationTester->run(array(
            'command' => 'logger:delete',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("Are you really sure you would like to delete all logs? Logs not deleted.", $this->applicationTester->getDisplay());
    }

    /**
     * @group Database
     * @group DropDatabase
     */
    public function testDropDatabaseShouldFail()
    {
        $this->applicationTester->setInputs(['no']);
        $code = $this->applicationTester->run(array(
            'command' => 'database:drop',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("Are you really sure you would like to drop the database? Not dropping db", $this->applicationTester->getDisplay());
    }
}

CommandsTest::$fixture = new OneVisitorTwoVisits();
