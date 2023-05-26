<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\tests\Unit;

use Piwik\Plugins\ExtraTools\Lib\Archivers;
use Piwik\Tests\Fixtures\SomePageGoalVisitsWithConversions;

/**
 * @group ExtraTools
 * @group Archivers
 * @group Plugins
 */
class UnitTestsTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var SomePageGoalVisitsWithConversions
     */
    public static $fixture;

    public function setUp(): void
    {
        parent::setUp();
        UnitTestsTest::$fixture = new SomePageGoalVisitsWithConversions();
    }

    public function tearDown(): void
    {
        // tear down here if needed
    }

    public function test_list_archivers()
    {
        $archiver= new Archivers();
        $result = $archiver->getAllInvalidations();

       // $this->assertTrue($result);

      //  $notificationsInArray = Manager::getPendingInMemoryNotifications();

        // $expected = [
        //     'testid' => $notification,
        // ];
        // $this->assertEquals($expected, $notificationsInArray);
    }


}

