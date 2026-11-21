<?php

namespace Piwik\Plugins\ExtraToolsDummy;

use Piwik\Updater;
use Piwik\Updater\Migration\Factory as MigrationFactory;
use Piwik\Updates as PiwikUpdates;

class Updates_1_1_0 extends PiwikUpdates
{
    private MigrationFactory $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        return [
            $this->migration->db->addColumn(ExtraToolsDummy::TABLE, ExtraToolsDummy::COLUMN_OF_UPDATE, 'INT NOT NULL DEFAULT 0'),
        ];
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
