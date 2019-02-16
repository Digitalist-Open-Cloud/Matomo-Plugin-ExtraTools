<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Install\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\ErrorHandler;
use Piwik\ExceptionHandler;
use Piwik\FrontController;
use Piwik\Access;
use Piwik\Common;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugins\SitesManager\API as APISitesManager;
use Piwik\Config;
use Piwik\Filesystem;
use Piwik\DbHelper;
use Piwik\Updater;
use Piwik\Plugin\Manager;
use Piwik\Container\StaticContainer;
use Piwik\Option;

use Piwik\Plugins\Install\Lib\Install;


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
        $this->setName('install:matomo');
        $this->setDescription('Install Matomo');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Your name:');
    }

    /**
     * Execute the command like: ./console install:install-matomo --name="The Piwik Team"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $name = $input->getOption('name');
        $config = [];
        $install = new Install($config);

        $message = sprintf('<info>InstallMatomo: %s</info>', $name);
        $output->writeln($message);

        $install->execute();

    }
}
