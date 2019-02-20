<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Create
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
        $db_host = $this->config['db_host'];
        $db_user = $this->config['db_user'];
        $db_pass = $this->config['db_pass'];
        $db_name = $this->config['db_name'];

        $drop = new Process\Process(
            "mysqladmin -u $db_user -h $db_host -p$db_pass create $db_name --force"
        );
        $drop->enableOutput();
        $drop->run();

        if (!$drop->isSuccessful()) {
            throw new ProcessFailedException($drop);
        } else {
            $text = 'Database "%s" created';
            $message = sprintf($text, $db_name);
            $this->output->writeln("<info>$message</info>");
        }
    }
}
