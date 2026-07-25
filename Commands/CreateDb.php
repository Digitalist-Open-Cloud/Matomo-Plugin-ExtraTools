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
use Piwik\Plugins\ExtraTools\Lib\Create;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class CreateDb extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will drop your db that is set in config.ini.php.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('database:create');
        $this->setDescription('Create database defined in config.ini.php');
        $this->addNoValueOption(
            'force',
            null,
            'force dropping without asking'
        );
    }

    /**
     * Execute the command
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $force = $input->getOption('force');
        $configs = Config::getInstance();
        // Only supporting local config.
        $db_configs = $configs->getFromLocalConfig('database');

        if ($force === false) {
            $question = $this->askForConfirmation('Are you really sure you would like to create the database? ', false);
            if (!$question) {
                echo "foo";
                //return self::FAILURE;
            } else {
                $force = true;
            }
        }
        if ($force === true) {
            if (!isset($db_configs['port'])) {
                $db_configs['port'] = '3306';
            }
            $config = [
                'db_host' => $db_configs['host'],
                'db_port' => $db_configs['port'],
                'db_user' => $db_configs['username'],
                'db_pass' => $db_configs['password'],
                'db_name' => $db_configs['dbname'],
            ];
            // Add SSL settings from the [database] section, if any.
            $config += \Piwik\Plugins\ExtraTools\Lib\DatabaseSsl::fromDatabaseConfig($db_configs);

            $create = new Create($config, $output);
            $output->writeln('<info>Dropping db:</info>');
            $create->execute();
            $output->writeln('<info>Database created</info>');
        }
        return self::SUCCESS;
    }
}
