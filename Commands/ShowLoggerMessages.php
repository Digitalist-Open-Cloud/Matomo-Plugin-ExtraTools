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
use Piwik\Plugins\LogViewer\API as LoggerAPI;

class ShowLoggerMessages extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will delete internal logger messages in the database.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('logger:show');
        $this->setDescription('Show internal logger messages in database (monolog)');
        $this->addOptionalValueOption('query', null, 'Query logs for this', '');
        $this->addOptionalValueOption('results', 'r', 'Number of results. Defaults to 100', '100');
        $this->addOptionalValueOption('format', 'f', 'output format, stdout, json or csv, defaults to stdout.', 'stdout');
        $this->addOptionalValueOption('file', null, 'If using csv output, output to this file', 'logger.csv');
    }

    /**
     * List users.
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $query = $input->getOption('query');
        $results = $input->getOption('results');
        $format = $input->getOption('format');
        $file = $input->getOption('file');
        $show = new LoggerAPI();
        $logs = $show->getLogEntries($query, false, 0, $results);
        if ($format == 'stdout') {
            foreach ($logs as $log) {
                $output->writeln('<comment>Severity: ' . $log['severity'] . '</comment>');
                $output->writeln('<info>Tag: ' . $log['tag'] . '</info>');
                $output->writeln('<info>Datetime: ' . $log['datetime'] . '</info>');
                $output->writeln('<info>Request id: ' . $log['requestId'] . '</info>');
                $output->writeln('<info>Message: ' . $log['message'] . '</info>');
                $output->writeln('<info>***</info>');
            }
        }
        if ($format == 'csv') {
            $fp = fopen($file, 'w');
            foreach ($logs as $log) {
                fputcsv($fp, $log);
            }
            $output->writeln("<comment>Finished</comment>");
            $output->writeln("<info>Logs were written to: $file</info>");
        }
        if ($format == 'json') {
            $out = json_encode($logs);
            $output->writeln("<info>$out</info>");
        }
        return self::SUCCESS;
    }
}
