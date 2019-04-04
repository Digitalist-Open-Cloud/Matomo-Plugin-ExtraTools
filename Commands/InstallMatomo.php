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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Piwik\Plugins\ExtraTools\Lib\Drop;
use Piwik\Plugins\ExtraTools\Lib\Create;
use Piwik\Common;
use stdClass;

use Piwik\Plugins\ExtraTools\Lib\Install;

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
Example:
<info> %command.name% --install-file=install.json</info>
<info>
 See more examples in README.md
</info>  
  ';
        $this->setHelp($HelpText);
        $this->setName('matomo:install');
        $this->setDescription('Install Matomo');
        $this->addOption(
            'install-file',
            null,
            InputOption::VALUE_REQUIRED,
            'Install from this file'
        );
        $this->addOption(
            'first-user',
            null,
            InputOption::VALUE_OPTIONAL,
            'First user name',
            getenv('MATOMO_FIRST_USER_NAME')
        );
        $this->addOption(
            'first-user-email',
            null,
            InputOption::VALUE_OPTIONAL,
            'First user email',
            getenv('MATOMO_FIRST_USER_EMAIL')
        );
        $this->addOption(
            'first-user-pass',
            null,
            InputOption::VALUE_OPTIONAL,
            'First user password',
            getenv('MATOMO_FIRST_USER_PASSWORD')
        );
        $this->addOption(
            'first-site-name',
            null,
            InputOption::VALUE_OPTIONAL,
            'First site name',
            getenv('MATOMO_FIRST_SITE_NAME')
        );
        $this->addOption(
            'first-site-url',
            null,
            InputOption::VALUE_OPTIONAL,
            'First site url',
            getenv('MATOMO_FIRST_SITE_URL')
        );
        $this->addOption(
            'db-username',
            null,
            InputOption::VALUE_OPTIONAL,
            'DB user name',
            getenv('MATOMO_DB_USERNAME')
        );
        $this->addOption(
            'db-pass',
            null,
            InputOption::VALUE_OPTIONAL,
            'DB password',
            getenv('MATOMO_DB_PASSWORD')
        );
        $this->addOption(
            'db-host',
            null,
            InputOption::VALUE_OPTIONAL,
            'DB host',
            getenv('MATOMO_DB_HOST')
        );
        $this->addOption(
            'db-name',
            null,
            InputOption::VALUE_OPTIONAL,
            'DB name',
            getenv('MATOMO_DB_NAME')
        );
        $this->addOption(
            'db-prefix',
            null,
            InputOption::VALUE_OPTIONAL,
            'DB tables prefix',
            getenv('MATOMO_DB_PREFIX')
        );
        $this->addOption(
            'plugins',
            null,
            InputOption::VALUE_OPTIONAL,
            'Plugins to install (comma separated)',
            getenv('MATOMO_PLUGINS')
        );
        $this->addOption(
            'timestamp',
            null,
            InputOption::VALUE_NONE,
            'Adds timestamp to the log'
        );
        $this->addOption(
            'do-not-drop-db',
            null,
            InputOption::VALUE_NONE,
            'Do not drop database'
        );
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'force installing without asking',
            null
        );
    }

    /**
     * Execute the command like: ./console install:install-matomo
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getOption('install-file');
        $first_user = $input->getOption('first-user');
        $first_user_email = $input->getOption('first-user-email');
        $first_user_password = $input->getOption('first-user-pass');
        $first_site_name= $input->getOption('first-site-name');
        $first_site_url = $input->getOption('first-site-url');
        $db_username = $input->getOption('db-username');
        $db_pass = $input->getOption('db-pass');
        $db_host = $input->getOption('db-host');
        $db_name = $input->getOption('db-name');
        $db_prefix = $input->getOption('db-prefix');
        $plugins = $input->getOption('plugins');
        $timestamp = $input->getOption('timestamp') ? true : false;
        $dontdropdb = $input->getOption('do-not-drop-db') ? true : false;
        $force = $input->getOption('force');

        $env_timestamp = getenv('MATOMO_LOG_TIMESTAMP');
        if (isset($env_timestamp)) {
            if ($env_timestamp == true) {
                $timestamp = $env_timestamp;
            }
        }


        $file_config = $this->fileConfig($file);

        $options = [
            'first-user' => $first_user,
            'first-user-email' => $first_user_email,
            'first-user-password' => $first_user_password,
            'first-site-name' => $first_site_name,
            'first-site-url' => $first_site_url,
            'db-username' => $db_username,
            'db-pass' =>  $db_pass,
            'db-host' => $db_host,
            'db-name' => $db_name,
            'db-prefix' => $db_prefix,
            'timestamp' => $timestamp,
            'plugins' => $plugins,
            'do-not-drop-db' => $dontdropdb
        ];

        $config = [
            'db_host' => $db_host,
            'db_user' => $db_username,
            'db_pass' => $db_pass,
            'db_name' => $db_name,
        ];

        if ($force === false) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you really sure you would like to install Matomo - if you have an installation already, it will be wiped? ', false);
            if (!$helper->ask($input, $output, $question)) {
                return;
            } else {
                $force = true;
            }
        }

        if ($force === true) {
            if ($dontdropdb === false) {
                $drop = new Drop($config, $output);
                $drop->execute();
                $create = new Create($config, $output);
                $create->execute();
            }

            $install = new Install($options, $output, $file_config);

            $output->writeln("<info><comment>Installing Matomo</comment></info>");
            $install->execute();
        }
    }

    private function readconf($file)
    {
        $json = json_decode(
            file_get_contents($file),
            true
        );

        if (Common::hasJsonErrorOccurred()) {
            throw new \Exception(
                $this->log("<error>" . Common::getLastJsonError() . "</error>")
            );
        }
        return $json;
    }

    private function fileConfig($file)
    {
        if (file_exists($file)) {
            $config = new stdClass();
            $config->Config = $this->readconf($file);
            return $config;
        }
        return false;
    }
    /**
     * Write an output log.
     * @param $text string
     */
    protected function log($text)
    {
        $output = new ConsoleOutput();
        $output->writeln("<info>$text</info>");
    }
}
