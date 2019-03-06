<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class User
{

    protected $config;
    protected $super_user;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct($config, OutputInterface $output, $super_user = false)
    {
        $this->config = $config;
        $this->output = $output;
        $this->super_user = $super_user;
    }

    public function execute()
    {
    }
}
