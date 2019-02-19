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
use stdClass;

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
Example 1:
<info>> %command.name% --db-username=myuser --db-pass=password \
  --db-host=localhost --db-name=matomo --first-site-name=Foo \
  --first-site-url=https//foo.bar --first-user=\'Mr Foo Bar\' \
  --first-user-email= foo@bar.com --first-user-pass=secret</info>
Example 2 (using environment variables, docker-compose.yml example):
<info>
environment:
      - MATOMO_DB_USERNAME=myuser
      - MATOMO_DB_PASSWORD=secret
      - MATOMO_DB_HOST=mysql
      - MATOMO_DB_NAME=matomo
      - MATOMO_FIRST_USER_NAME=Mr Foo Bar
      - MATOMO_FIRST_USER_EMAIL=foo@bar.com
      - MATOMO_FIRST_USER_PASSWORD=secret
      - MATOMO_FIRST_SITE_NAME=Foo
      - MATOMO_FIRST_SITE_URL=https://foo.bar
--
> %command.name% 
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
    }

    /**
     * Execute the command like: ./console install:install-matomo --new
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
        ];

        $config = [
            'db_host' => $db_host,
            'db_user' => $db_username,
            'db_pass' => $db_pass,
            'db_name' => $db_name,
        ];

        $drop = new Drop($config, $output);
        $drop->execute();

        $create = new Create($config, $output);
        $create->execute();

        $install = new Install($options, $output, $file_config);

        $output->writeln("<info><comment>Installing Matomo</comment></info>");
        $install->execute();
    }

    private function readconf($file) {
        $config = json_decode(
            file_get_contents($file), TRUE
        );
        return $config;
    }

    private function fileConfig($file) {
        if (file_exists($file)) {
            $config = new stdClass();
            $config->Config = $this->readconf($file);
            return $config;
        }
        return false;

    }
}
