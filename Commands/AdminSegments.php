<?php

/**
 * The Extra Tools plugin for Matomo.
 *
 * Copyright (C) Digitalist Open Cloud <cloud@digitalist.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Common;
use Piwik\Db;
use Piwik\Date;

/**
 * Class AdminSegments
 * @package Piwik\Plugins\ExtraTools\Commands
 */
class AdminSegments extends ConsoleCommand
{
    private static $rawPrefix = 'segment';

    protected function getTable()
    {
        return Common::prefixTable(self::$rawPrefix);
    }


    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list att your segments.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('segment:admin');
        $this->setDescription('Administrate segments');
        $this->addOptionalValueOption(
            'delete-segment',
            null,
            'Segment id to delete',
            null
        );
        $this->addOptionalValueOption(
            'activate-segment',
            null,
            'Segment id to activate (undo delete)',
            null
        );
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $deleteSegment = $input->getOption('delete-segment');
        $activateSegment = $input->getOption('activate-segment');
        $info = '';

        if (isset($deleteSegment)) {
            $segmentId = $deleteSegment;
        }
        if (isset($activateSegment)) {
            $segmentId = $activateSegment;
        }
        if (!isset($segmentId)) {
            $output->writeln("<error>You need to provide a segment id</error>");
            return self::FAILURE;
        }
        $validate = filter_var($segmentId, FILTER_VALIDATE_INT) !== false;
        if (!$validate) {
            $output->writeln("<error>You need to provide a single int id for segment (ex. 1)</error>");
            return self::FAILURE;
        }

        $segment = $this->getSegment($segmentId);

        if (!$segment) {
            $output->writeln("<error>You need to provide an existing segment id</error>");
            return self::FAILURE;
        }

        if (isset($deleteSegment)) {
            $this->deleteSegment($segmentId);
            $info = "Segment id $segmentId marked as deleted";
        }

        if (isset($activateSegment)) {
            $this->unDeleteSegment($segmentId);
            $info = "Segment id $segmentId is now active";
        }


        $output->writeln("<info>$info</info>");
        return self::SUCCESS;
    }


    /**
     * @param $segment
     * @return array
     * @throws \Exception
     */
    public function getSegment($idSegment)
    {
        $segment = Db::get()->fetchRow("SELECT * FROM " . $this->getTable() . " WHERE idsegment = ?", $idSegment);

        return $segment;
    }

    /**
     * @param $idSegment
     * @throws \Exception
     */
    public function deleteSegment($idSegment)
    {
        $this->setDeleted($idSegment, 1);
    }

    /**
     * @param $idSegment
     * @throws \Exception
     */
    public function unDeleteSegment($idSegment)
    {
        $this->setDeleted($idSegment, 0);
    }

    private function setDeleted($idSegment, int $deleted): void
    {
        Db::query(
            'UPDATE ' . $this->getTable() . ' SET deleted = ?, ts_last_edit = ? WHERE idsegment = ?',
            [$deleted, Date::factory('now')->toString('Y-m-d H:i:s'), (int) $idSegment]
        );
    }
}
