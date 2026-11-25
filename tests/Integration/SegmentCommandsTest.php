<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\ArchiveProcessor\Rules;
use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\SegmentEditor\Model;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group ExtraTools
 * @group Plugins
 * @group Segment
 */
class SegmentCommandsTest extends ConsoleCommandTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Matomo only lets a segment be processed when archiving if reports do not start the archiving.
        Rules::setBrowserTriggerArchiving(false);
        // The database is kept between the tests of this class.
        if (!Db::fetchOne('SELECT COUNT(*) FROM ' . Common::prefixTable('site'))) {
            Fixture::createWebsite('2020-01-01 00:00:00');
        }
    }

    public function testASegmentIsAddedForAllSitesAndAllUsers()
    {
        $id = $this->add(['--name' => 'Tom & Jerry', '--definition' => 'actions>=2;countryCode==se']);

        $this->assertSame(['Tom &amp; Jerry', 'actions>=2;countryCode==se', 0, 1, 0], $this->getStored($id));
    }

    public function testASegmentCanBeAddedForOneSiteAndProcessedWhenArchiving()
    {
        $id = $this->add(['--name' => 'Swedes', '--definition' => 'countryCode==se', '--site' => '1', '--auto-archive' => 'true']);

        $this->assertSame(['Swedes', 'countryCode==se', 1, 1, 1], $this->getStored($id));
    }

    public function testOnlyWhatIsGivenIsChanged()
    {
        $id = $this->add(['--name' => 'Tom & Jerry', '--definition' => 'actions>=2', '--site' => '1', '--auto-archive' => 'true']);

        $this->assertSame(0, $this->save(['--id' => $id, '--name' => 'Cat & mouse']), $this->applicationTester->getDisplay());
        $this->assertSame(['Cat &amp; mouse', 'actions>=2', 1, 1, 1], $this->getStored($id));

        $this->assertSame(0, $this->save(['--id' => $id, '--definition' => 'countryCode==se', '--site' => 'all', '--auto-archive' => 'false']));
        $this->assertSame(['Cat &amp; mouse', 'countryCode==se', 0, 1, 0], $this->getStored($id));
    }

    public function testTheListTellsWhereASegmentIsUsedAndHowItIsProcessed()
    {
        $id = $this->add(['--name' => 'Listed', '--definition' => 'countryCode==dk', '--auto-archive' => 'true']);

        $this->assertSame(0, $this->applicationTester->run(['command' => 'segment:list']));

        $listed = substr($this->applicationTester->getDisplay(), strpos($this->applicationTester->getDisplay(), "Segment ID: $id\n"));
        $this->assertStringContainsString('Enabled for: all sites', $listed);
        $this->assertStringContainsString('Segment is pre-processed (cron)', $listed);
        $this->assertStringContainsString('Segment is: active', $listed);
    }

    /**
     * @dataProvider getWhatIsRefused
     */
    public function testWhatCanNotBeSavedIsRefused(array $options, bool $ofExisting = false)
    {
        $id = $this->add(['--name' => 'Swedes', '--definition' => 'countryCode==se']);
        $before = $this->getAllStored();

        $this->assertSame(1, $this->save($options + ($ofExisting ? ['--id' => $id] : [])));

        $this->assertSame($before, $this->getAllStored());
    }

    public function getWhatIsRefused(): array
    {
        return [
            'no name' => [['--definition' => 'countryCode==se']],
            'no definition' => [['--name' => 'Swedes']],
            'a definition Matomo can not read' => [['--name' => 'Odd', '--definition' => 'noSuchThing==1']],
            'a site that does not exist' => [['--name' => 'Odd', '--definition' => 'countryCode==no', '--site' => '99']],
            'a site that is not an ID' => [['--name' => 'Odd', '--definition' => 'countryCode==no', '--site' => 'first']],
            'a segment that does not exist' => [['--id' => '99999', '--name' => 'Odd']],
            'nothing to change' => [[], true],
            'a broken definition for an existing segment' => [['--definition' => 'noSuchThing==1'], true],
            'an auto archive that is not true or false' => [['--auto-archive' => 'yes'], true],
        ];
    }

    private function save(array $options): int
    {
        return $this->applicationTester->run(['command' => 'segment:save'] + $options);
    }

    /**
     * @return string the ID of the segment that was added
     */
    private function add(array $options): string
    {
        $this->assertSame(0, $this->save($options), $this->applicationTester->getDisplay());
        $this->assertSame(1, preg_match('/added with ID (\d+)/', $this->applicationTester->getDisplay(), $found));

        return $found[1];
    }

    private function getAllStored(): array
    {
        return Db::fetchAll('SELECT name, definition, enable_only_idsite, auto_archive, deleted FROM ' . Common::prefixTable('segment') . ' ORDER BY idsegment');
    }

    private function getStored(string $idSegment): array
    {
        $segment = (new Model())->getSegment((int) $idSegment);

        return [
            $segment['name'],
            $segment['definition'],
            (int) $segment['enable_only_idsite'],
            (int) $segment['enable_all_users'],
            (int) $segment['auto_archive'],
        ];
    }
}
