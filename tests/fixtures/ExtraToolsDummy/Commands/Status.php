<?php

namespace Piwik\Plugins\ExtraToolsDummy\Commands;

use Piwik\Common;
use Piwik\Db;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\ExtraToolsDummy\ExtraToolsDummy;
use Piwik\Updater;

class Status extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('extratoolsdummy:status');
        $this->setDescription('Tells which version the database is at for this plugin, and whether its update was run.');
    }

    protected function doExecute(): int
    {
        $column = Db::fetchAll(
            'SHOW COLUMNS FROM `' . Common::prefixTable(ExtraToolsDummy::TABLE) . '` LIKE ?',
            [ExtraToolsDummy::COLUMN_OF_UPDATE]
        );

        $this->getOutput()->writeln(sprintf(
            'version=%s column=%s',
            (new Updater())->getCurrentComponentVersion('ExtraToolsDummy'),
            $column ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }
}
