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

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Config;
use Piwik\Plugins\ExtraTools\Lib\Backup;

class BackupDb extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will backup your db.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>
You could use options to override config or environment variables:
<info>%command.name% --db-backup-path=/tmp/foo</info>';
        $this->setHelp($HelpText);
        $this->setName('database:backup');
        $this->setDescription('Backup database');
        $this->addOptionalValueOption(
            'backup-path',
            'b',
            'backup path',
            null
        );
        $this->addOptionalValueOption(
            'backup-prefix',
            'p',
            'prefix for backup name',
            'backup'
        );
        $this->addOptionalValueOption(
            'timeout',
            't',
            'timeout for the process',
            '60'
        );
    }

    /**
     * Execute the command like: ./console backup:db"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $backup_folder = $input->getOption('backup-path');
        $backup_prefix = $input->getOption('backup-prefix');
        $timeout = $input->getOption('timeout');
        // check if we have db backup path in config
        $configs = Config::getInstance();
        $matomo_tools_config = $configs->getFromLocalConfig('ExtraTools');
        if (!isset($backup_folder)) {
            if (isset($matomo_tools_config['db_backup_path'])) {
                $backup_folder = $matomo_tools_config['db_backup_path'];
            }
        }

        if ($backup_folder == null) {
            $output->writeln("<error>Value for backup-path is required</error>");
            return self::FAILURE;
        }
        $configs = Config::getInstance();
        // Only supporting local config.
        $db_configs = $configs->getFromLocalConfig('database');
        // port is not always set.
        if (!isset($db_configs['port'])) {
            $db_configs['port'] = '3306';
        }
        $config = [
            'db_host' =>  $db_configs['host'],
            'db_port' =>  $db_configs['port'],
            'db_user' => $db_configs['username'],
            'db_pass' => $db_configs['password'],
            'db_name' =>  $db_configs['dbname'],
            'db_backup_folder' => $backup_folder,
            'db_backup_prefix' => $backup_prefix,
            'timeout' => $timeout,
        ];
        // Add SSL settings from the [database] section, if any.
        $config += \Piwik\Plugins\ExtraTools\Lib\DatabaseSsl::fromDatabaseConfig($db_configs);


        $backup = new Backup($config, $output);
        $output->writeln('<info>Starting backup job:</info>');
        $backup->execute();
        return self::SUCCESS;
    }
}
