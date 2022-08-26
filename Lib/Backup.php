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
        $db_port = $this->config['db_port'];
        $db_user = $this->config['db_user'];
        $db_pass = $this->config['db_pass'];
        $db_name = $this->config['db_name'];
        $prefix  = $this->config['db_backup_prefix'];
	
	// build temp db config file
	$temp = tmpfile();
	fwrite($temp,
		"[client]" . "\n" .
		"user=" . $db_user . "\n" .
		"password=" . $db_pass
	);
	$config_path = stream_get_meta_data($temp)['uri'];

        $timestamp = date("Ymd-His");

	$backup = new Process\Process(
		"mysqldump --defaults-extra-file=$config_path -h $db_host -P $db_port --no-data $db_name --add-drop-table > $backup_folder/$prefix-$timestamp.sql 2> /dev/tty"
		#"mysqldump -u 2$db_user -h$host -P $db_port -p$db_pass --no-data $db_name --add-drop-table > $backup_folder/$prefix-$timestamp.sql 2> /dev/tty"
	);

        $backup->enableOutput();
	$backup->run();

	// remove temp file
	fclose($temp);

        echo $backup->getOutput();
        if (!$backup->isSuccessful()) {
            throw new ProcessFailedException($backup);
        } else {
            $this->output->writeln(
                "<info>Backup done, dump at <comment>$backup_folder/$prefix-$timestamp.sql</comment></info>"
            );
        }
    }
}
