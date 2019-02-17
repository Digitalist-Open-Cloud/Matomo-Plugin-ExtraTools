<?php

namespace Piwik\Plugins\MatomoExtraTools\Lib;

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
use Piwik\Db\Schema;
use Piwik\Updater;
use Piwik\Plugin\Manager;
use Piwik\Container\StaticContainer;
use Piwik\Option;
use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Backup
{

    protected $config;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct($config, OutputInterface $output)
    {
        $this->config = $config;
        $this->output = $output;
    }

    public function execute()
    {
        // Fetch config.
        $backup_folder = $this->config['db_backup_folder'];
        $db_host = $this->config['db_host'];
        $db_user = $this->config['db_user'];
        $db_pass = $this->config['db_pass'];
        $db_name = $this->config['db_name'];
        $prefix  = $this->config['db_backup_prefix'];

        $timestamp = date("Ymd-His");
        $backup = new Process\Process(
            "mysqldump -u $db_user -h $db_host -p$db_pass $db_name >" .
            "$backup_folder/$prefix-$timestamp.sql"
        );
        $backup->enableOutput();

        $backup->run();
        echo $backup->getOutput();
        if (!$backup->isSuccessful()) {
            throw new ProcessFailedException($backup);
        } else {
            $this->output->writeln("<info>Backup done, dump at <comment>$backup_folder/$prefix-$timestamp.sql</comment></info>");
        }
    }
}
