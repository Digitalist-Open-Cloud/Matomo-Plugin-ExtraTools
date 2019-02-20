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
use stdClass;

use Piwik\Plugins\ExtraTools\Lib\Install;

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
        $this->addOption(
            'timestamp',
            null,
            InputOption::VALUE_NONE,
            'Adds timestamp to the log'
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
        $timestamp = $input->getOption('timestamp') ? true : false;
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
            'timestamp' => $timestamp,
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
            $drop = new Drop($config, $output);
            $drop->execute();

            $create = new Create($config, $output);
            $create->execute();

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
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ''; // JSON is valid // No error has occurred
                break;
            case JSON_ERROR_DEPTH:
                $error = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON.';
                break;
            // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                $error = 'One or more recursive references in the value to be encoded.';
                break;
            // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                $error = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $error = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $error = 'Unknown JSON error occured.';
                break;
        }

        if ($error !== '') {
            // throw the Exception or exit // or whatever :)
            exit($this->log("<error>$error</error>"));
        }

        // everything is OK
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
