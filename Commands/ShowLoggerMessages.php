<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Db;
use Piwik\Common;
use Symfony\Component\Console\Input\InputOption;
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
        $this->setDefinition(
            [
                new InputOption(
                    'query',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Query logs for this',
                    ''
                ),
                new InputOption(
                    'results',
                    'r',
                    InputOption::VALUE_OPTIONAL,
                    'Number of results. Defaults to 100',
                    '100'
                ),
                new InputOption(
                    'format',
                    'f',
                    InputOption::VALUE_OPTIONAL,
                    'output format, stdout, json or csv, defaults to stdout.',
                    'stdout'
                ),
                new InputOption(
                    'file',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'If using csv output, output to this file',
                    'logger.csv'
                ),
            ]
        );
    }

    /**
     * List users.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        return 0;
    }
}
