<?php

/**
 * The Extra Tools plugin for Matomo.
 *
 * Copyright (C) Digitalist Open Cloud <cloud@digitalist.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
        $db_name = $this->config['db_name'];
        $prefix = $this->config['db_backup_prefix'];
        $timeout = $this->config['timeout'];

        // Build a temp db config file ([client] group with credentials and,
        // when enabled, SSL settings).
        [$temp, $config_path] = DatabaseSsl::createClientOptionFile($this->config);
        $timestamp = date("Ymd-His");
        $backup = Process\Process::fromShellCommandline("mysqldump --defaults-extra-file=$config_path -h $db_host -P $db_port $db_name --add-drop-table > $backup_folder/$prefix-$timestamp.sql");
        $backup->setTimeout($timeout);
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
