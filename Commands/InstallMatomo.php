<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MatomoExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Config;
use Piwik\Plugins\MatomoExtraTools\Lib\Drop;
use Piwik\Plugins\MatomoExtraTools\Lib\Create;

use Piwik\Plugins\MatomoExtraTools\Lib\Install;

if (file_exists(PIWIK_DOCUMENT_ROOT . '/bootstrap.php')) {
    require_once PIWIK_DOCUMENT_ROOT . '/bootstrap.php';
}

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class InstallMatomo extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will install Matomo.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>
To reinstall site - warning - this will remove your current sites db:
<info>%command.name% --re-install</info>';
        $this->setHelp($HelpText);
        $this->setName('matomo:install');
        $this->setDescription('Install Matomo');
        $this->addOption(
            'install-file',
            null,
            InputOption::VALUE_REQUIRED,
            'Install from this file'
        );
    }

    /**
     * Execute the command like: ./console install:install-matomo --new
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption('install-file');
        $configs = Config::getInstance();
        // Only supporting local config.
        $db_configs = $configs->getFromLocalConfig('database');

        $config = [
            'db_host' =>  $db_configs['host'],
            'db_user' => $db_configs['username'],
            'db_pass' => $db_configs['password'],
            'db_name' =>  $db_configs['dbname'],
        ];

        $drop = new Drop($config, $output);
        $drop->execute();

        $create = new Create($config, $output);
        $create->execute();

        $install = new Install($config, $output);

        $output->writeln("<info>Install Matomo</info>");
        $install->execute();
    }
}
