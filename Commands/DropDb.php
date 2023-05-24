<?php

/**
 * ExtraTools
 *
 * @link https://github.com/digitalist-se/extratools
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Piwik\Config;
use Piwik\Plugins\ExtraTools\Lib\Drop;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class DropDb extends ConsoleCommand
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
        $this->setName('database:drop');
        $this->setDescription('Drops database - backup first if needed.');
        $this->addNoValueOption(
            'force',
            null,
            'force dropping without asking',
            null
        );
    }

    /**
     * Execute the command like: ./console backup:db"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $force = $input->getOption('force');

        $configs = Config::getInstance();
        $db_configs = $configs->getFromLocalConfig('database');
        if (!isset( $db_configs['port'])) {
          $db_configs['port'] = '3306';
        }
        $config = [
            'db_host' =>  $db_configs['host'],
            'db_port' =>  $db_configs['port'],
            'db_user' => $db_configs['username'],
            'db_pass' => $db_configs['password'],
            'db_name' =>  $db_configs['dbname'],
        ];
        if ($force === false) {
            $question = $this->askForConfirmation("'Are you really sure you would like to drop the database? ", false);
            if (!$question) {
                $output->writeln('<info>Not dropping db</info>');
                return self::FAILURE;
            } else {
                $force = true;
            }
        }

        if ($force === true) {
            $backup = new Drop($config, $output);
            $output->writeln('<info>Dropping db:</info>');
            $backup->execute();
        }
        return self::SUCCESS;
    }
}
