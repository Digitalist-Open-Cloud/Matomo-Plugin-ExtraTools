<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Piwik\Plugins\CoreAdminHome\Commands\SetConfig\ConfigSettingManipulation;
use Piwik\Config as Config;

class ConfigManipulation
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

    public function saveConfig($section, $key, $value)
    {

        $manipulations = [];
        $isSingleAssignment = !empty($section) && !empty($key) && $value !== false;
        if ($isSingleAssignment) {
            if (is_array($section)) {
                $manipulations[] = new ConfigSettingManipulation($section, $key, $value, true);
            } else {
                $manipulations[] = new ConfigSettingManipulation($section, $key, $value);
            }
        }

        $config = Config::getInstance();
        foreach ($manipulations as $manipulation) {
            $manipulation->manipulate($config);
        }
        $config->forceSave();
    }
}
