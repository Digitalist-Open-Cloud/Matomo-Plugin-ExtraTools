<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Common;


/**
 * @group ExtraTools
 * @group Plugins
 * @group Console
 */
class CommandsTest extends ConsoleCommandTestCase
{

    public function testSiteAddWithoutWebsiteName()
    {
        $code = $this->applicationTester->run(array(
            'command' => 'site:add',
            '-vvv' => true,
        ));
        $this->assertEquals(1, $code);
        $this->assertStringContainsStringIgnoringCase("The website name can't be empty", $this->applicationTester->getDisplay());
    }

}

