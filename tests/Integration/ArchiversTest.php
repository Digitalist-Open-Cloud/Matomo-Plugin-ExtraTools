<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\ExtraTools\Lib\Archivers;
use Piwik\Plugins\SegmentEditor\API as SegmentEditorApi;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group ExtraTools
 * @group Plugins
 */
class ArchiversTest extends IntegrationTestCase
{
    public function provideContainerConfig()
    {
        return ['Piwik\Access' => new FakeAccess()];
    }

    public function testAnInvalidationIsNamedAfterItsSegmentAndPlugin()
    {
        FakeAccess::clearAccess(true);
        $idSite = Fixture::createWebsite('2024-01-01 00:00:00');
        $idSegment = SegmentEditorApi::getInstance()->add('Returning visitors', 'visitorType==returning', $idSite);
        $hash = Db::fetchOne('SELECT `hash` FROM ' . Common::prefixTable('segment') . ' WHERE idsegment = ?', [$idSegment]);
        $unknown = str_repeat('a', 32);
        $archivers = new Archivers();

        $this->assertSame('All visits', $archivers->getLabel('done'));
        $this->assertSame('All visits (Goals)', $archivers->getLabel('done.Goals'));
        $this->assertSame('Returning visitors', $archivers->getLabel("done$hash"));
        $this->assertSame('Returning visitors (Goals)', $archivers->getLabel("done$hash.Goals"));
        $this->assertSame($unknown, $archivers->getLabel("done$unknown"), 'the segment was deleted');
        $this->assertSame('something else', $archivers->getLabel('something else'));
    }
}
