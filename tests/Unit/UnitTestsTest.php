<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\tests\Unit;

/**
 * @group ExtraTools
 * @group UnitTestsTest
 * @group Plugins
 */
class UnitTestsTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        // set up here if needed
    }
    
    public function tearDown(): void
    {
        // tear down here if needed
    }

    /**
     * All your actual test methods should start with the name "test"
     */
    public function testSimpleAddition()
    {
        $this->assertEquals(2, 1+1);
    }

}
