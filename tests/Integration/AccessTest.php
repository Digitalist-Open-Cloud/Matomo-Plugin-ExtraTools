<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\NoAccessException;
use Piwik\Plugins\ExtraTools\API;
use Piwik\Plugins\ExtraTools\Controller;
use Piwik\Plugins\ExtraTools\Lib\Install;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @group ExtraTools
 * @group Plugins
 */
class AccessTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Fixture::createWebsite('2024-01-01 00:00:00');
    }

    public function provideContainerConfig()
    {
        return ['Piwik\Access' => new FakeAccess()];
    }

    public function testASuperUserCanReadPhpInfo()
    {
        FakeAccess::clearAccess(true);

        $this->assertStringContainsString('PHP Version', API::getInstance()->getPhpInfo());
    }

    public function testAnAdminOfAWebsiteCannotReadPhpInfo()
    {
        FakeAccess::clearAccess(false, [1]);

        $this->expectException(NoAccessException::class);
        API::getInstance()->getPhpInfo();
    }

    public function testOnlyThePagesOfTheControllerCanBeRequested()
    {
        $actions = [];
        foreach ((new \ReflectionClass(Controller::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === Controller::class) {
                $actions[] = $method->getName();
            }
        }

        $this->assertSame(['index', 'docs', 'phpinfo', 'invalidatedarchives'], $actions);
    }

    /**
     * @dataProvider getOptionsWithoutPassword
     */
    public function testTheInstallerRefusesToCreateAFirstUserWithoutAPassword(array $options)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A password for the first user is required');

        (new Install($options, new NullOutput()))->execute();
    }

    public function getOptionsWithoutPassword(): array
    {
        return [
            'nothing given' => [[]],
            'empty option' => [['first-user' => 'admin', 'first-user-password' => '']],
        ];
    }
}
