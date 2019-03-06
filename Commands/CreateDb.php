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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Piwik\Config;

use Piwik\Plugins\ExtraTools\Lib\Create;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class CreateDb extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will drop your db that is set in config.ini.php.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('database:create');
        $this->setDescription('Create database defined in config.ini.php');
        $this->setDefinition(
            [
                new InputOption(
                    'force',
                    null,
                    InputOption::VALUE_NONE,
                    'force dropping without asking',
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
        $force = $input->getOption('force');
        $configs = Config::getInstance();
        // Only supporting local config.
        $db_configs = $configs->getFromLocalConfig('database');

        if ($force === false) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you really sure you would like to create the database? ', false);
            if (!$helper->ask($input, $output, $question)) {
                return;
            } else {
                $force = true;
            }
        }
        if ($force === true) {
            $config = [
                'db_host' => $db_configs['host'],
                'db_user' => $db_configs['username'],
                'db_pass' => $db_configs['password'],
                'db_name' => $db_configs['dbname'],
            ];

            $create = new Create($config, $output);
            $output->writeln('<info>Dropping db:</info>');
            $create->execute();
        }
    }
}
