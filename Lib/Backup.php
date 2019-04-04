<?php

namespace Piwik\Plugins\ExtraTools\Lib;

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
            "mysqldump -u $db_user -h $db_host -p$db_pass $db_name --add-drop-table >" .
            "$backup_folder/$prefix-$timestamp.sql" . " 2> >(grep -v \"Using a password\")"
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
