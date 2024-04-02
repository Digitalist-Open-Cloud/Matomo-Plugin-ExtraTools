<?php

/**
 * ExtraTools
 *
 * @link https://github.com/digitalist-se/extratools
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Piwik\Plugins\ExtraTools\Lib\Drop;
use Piwik\Plugins\ExtraTools\Lib\Create;
use Piwik\Common;
use stdClass;
use Piwik\Plugins\ExtraTools\Lib\Install;
use Piwik\Plugins\ExtraTools\Lib\Defaults;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class InstallMatomo extends ConsoleCommand
{
    public bool $silent;

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
        $this->addRequiredValueOption(
            'install-file',
            null,
            'Install from this file'
        );
        $this->addOptionalValueOption(
            'first-user',
            null,
            'First user name',
            $this->defaults()->firstSiteUserName()
        );
        $this->addOptionalValueOption(
            'first-user-email',
            null,
            'First user email',
            $this->defaults()->firstSiteUserEmail()
        );
        $this->addOptionalValueOption(
            'first-user-pass',
            null,
            'First user password',
            $this->defaults()->firstSiteUserPass()
        );
        $this->addOptionalValueOption(
            'first-site-name',
            null,
            'First site name',
            $this->defaults()->firstSiteName()
        );
        $this->addOptionalValueOption(
            'first-site-url',
            null,
            'First site url',
            $this->defaults()->firstSiteUrl()
        );
        $this->addOptionalValueOption(
            'db-username',
            null,
            'DB user name',
            $this->defaults()->dbUser()
        );
        $this->addOptionalValueOption(
            'db-pass',
            null,
            'DB password',
            $this->defaults()->dbPass()
        );
        $this->addOptionalValueOption(
            'db-host',
            null,
            'DB host',
            $this->defaults()->dbHost()
        );
        $this->addOptionalValueOption(
            'db-port',
            null,
            'DB port',
            $this->defaults()->dbPort()
        );
        $this->addOptionalValueOption(
            'db-name',
            null,
            'DB name',
            $this->defaults()->dbName()
        );
        $this->addOptionalValueOption(
            'db-prefix',
            null,
            'DB tables prefix',
            $this->defaults()->dbPrefix()
        );
        $this->addOptionalValueOption(
            'db-adapter',
            null,
            'DB adapter',
            $this->defaults()->dbAdapter()
        );
        $this->addOptionalValueOption(
            'plugins',
            null,
            'Plugins to install (comma separated)',
            $this->defaults()->plugins()
        );
        $this->addNoValueOption(
            'timestamp',
            null,
            'Adds timestamp to the log'
        );
        $this->addNoValueOption(
            'do-not-drop-db',
            null,
            'Do not drop database'
        );
        $this->addNoValueOption(
            'force',
            null,
            'force installing without asking',
            null
        );
        $this->addNoValueOption(
            'silent',
            null,
            'do not ouput anything',
            null
        );
    }

    /**
     * Execute the command like: ./console install:install-matomo
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $file = $input->getOption('install-file');
        $first_user = $input->getOption('first-user');
        $first_user_email = $input->getOption('first-user-email');
        $first_user_password = $input->getOption('first-user-pass');
        $first_site_name = $input->getOption('first-site-name');
        $first_site_url = $input->getOption('first-site-url');
        $db_username = $input->getOption('db-username');
        $db_pass = $input->getOption('db-pass');
        $db_host = $input->getOption('db-host');
        $db_port = $input->getOption('db-port');
        $db_name = $input->getOption('db-name');
        $db_prefix = $input->getOption('db-prefix');
        $db_adapter = $input->getOption('db-adapter');
        $plugins = $input->getOption('plugins');
        $timestamp = $input->getOption('timestamp') ? true : false;
        $dontdropdb = $input->getOption('do-not-drop-db') ? true : false;
        $force = $input->getOption('force');
        $silent = $input->getOption('silent');
        $this->silent = $silent;

        $env_timestamp = $this->defaults()->timestamp();
        if ($env_timestamp == true) {
            $timestamp = $env_timestamp;
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
            'db-port' => $db_port,
            'db-name' => $db_name,
            'db-prefix' => $db_prefix,
            'db-adapter' => $db_adapter,
            'timestamp' => $timestamp,
            'plugins' => $plugins,
            'do-not-drop-db' => $dontdropdb,
            'silent' => $silent
        ];

        $config = [
            'db_host' => $db_host,
            'db_port' => $db_port,
            'db_user' => $db_username,
            'db_pass' => $db_pass,
            'db_name' => $db_name,
            'db_adapter' => $db_adapter
        ];

        if ($force === false) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Are you really sure you would like to install Matomo - '
                . 'if you have an installation already, it will be wiped? ',
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                return self::SUCCESS;
            } else {
                $force = true;
            }
        }

        if ($force === true) {
            if ($dontdropdb === false) {
                $drop = new Drop($config, $output, $this->silent);
                $drop->execute();
                $create = new Create($config, $output, $this->silent);
                $create->execute();
            }
            $install = new Install($options, $output, $file_config);
            if ($this->silent !== true) {
                $output->writeln("<info><comment>Installing Matomo</comment></info>");
            }
            $install->execute();
        }
        return self::SUCCESS;
    }

    private function readconf($file)
    {
        $json = json_decode(
            file_get_contents($file),
            true
        );

        if (Common::hasJsonErrorOccurred()) {
            $this->log("<error> " .  Common::getLastJsonError() . "</error>");
            throw new \Exception(sprintf(
                'Not able to read file %s: %s',
                $file,
                Common::getLastJsonError()
            ));
        }
        return $json;
    }

    private function fileConfig($file)
    {
        if ($file !== null && file_exists($file)) {
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

    /**
     * Gets defaults config.
     * @return Defaults
     */
    public function defaults()
    {
        return new Defaults();
    }
}
