<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;
use Piwik\Tests\Framework\Fixture;
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
            '--id' => '1',
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
            '--id' => '1',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("You must provide an URL for the site", $this->applicationTester->getDisplay());
    }

    public function testSiteAddUrlWithNeededParametersShouldSucceed()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:url',
            '--id' => '1',
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



}


CommandsTest::$fixture = new OneVisitorTwoVisits();