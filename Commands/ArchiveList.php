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
use Piwik\Plugins\ExtraTools\Lib\Archivers;

class ArchiveList extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> wil list archives that are going
        or beeing archived.';
        $this->setHelp($HelpText);
        $this->setName('archive:list');
        $this->setDescription('List archivers listed for archivation');
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $get = $this->getArchivers();
        if (!empty($get)) {
            foreach ($get as $out) {
                $out_name = '';
                if ($out['name'] !== 'done') {
                    $out_name = "     segment: <comment>" . (new Archivers())->getLabel($out['name']) . "</comment>\n";
                }
                $out_period = '';
                if (isset($out['period'])) {
                    $period = [1 => 'day', 2 => 'week', 3 => 'month', 4 => 'year'][$out['period']] ?? $out['period'];
                    $out_period =  "     period: <comment>" . $period . "</comment>\n";
                }
                if (isset($out['started'])) {
                    $started = "     started: <comment>" . $out['started'] . "</comment>\n";
                } else {
                    $started = "     started: <comment>no</comment>\n";
                }
                $message = "idsite: <comment>" . $out['idsite'] . "</comment>\n"
                . $out_name
                . "     from: <comment>" . $out['date1'] . "</comment>\n"
                . "     to: <comment>" . $out['date2'] . "</comment>\n"
                . $out_period
                . "     invalidated: <comment>" . $out['ts_invalidated'] . "</comment>\n"
                . $started;
                $output->writeln("<info>$message</info>");
            }
        } else {
            $output->writeln("<info>No archivers ongoing or scheduled</info>");
        }
        return self::SUCCESS;
    }

    public function getArchivers()
    {
        $list = new Archivers();
        $out = $list->getAllInvalidations();
        return $out;
    }
}
