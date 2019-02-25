<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Config;

use Piwik\Plugins\ExtraTools\Lib\Import;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class ImportDB extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will backup your db.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('database:import');
        $this->setDescription('Import database - overwrites the default');
        $this->setDefinition(
            [
                new InputOption(
                    'backup-path',
                    'b',
                    InputOption::VALUE_OPTIONAL,
                    'backup path',
                    null
                ),
            ]
        );
    }

    /**
     * Execute the command like: ./console backup:db"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $backup_path = $input->getOption('backup-path');


        if ($backup_path == null) {
            $output->writeln("<error>Value for backup-path is required</error>");
            exit;
        }

        if (!file_exists($backup_path)) {
            $output->writeln("<error>Looks like backup does not exist or is not readable in $backup_path</error>");
            exit;
        }

        $configs = Config::getInstance();
        // Only supporting local config.
        $db_configs = $configs->getFromLocalConfig('database');

        $config = [
            'db_host' =>  $db_configs['host'],
            'db_user' => $db_configs['username'],
            'db_pass' => $db_configs['password'],
            'db_name' =>  $db_configs['dbname'],
            'db_backup_path' => $backup_path,
        ];

        $backup = new Import($config, $output);
        $output->writeln('<info>Starting import db job:</info>');
        $backup->execute();
    }
}
