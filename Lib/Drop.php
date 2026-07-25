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

class Drop
{
    protected $config;
    public bool $silent;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct($config, OutputInterface $output, $silent = 0)
    {
        $this->config = $config;
        $this->output = $output;
        $this->silent = $silent;
    }

    public function execute()
    {
        $db_host = $this->config['db_host'];
        $db_port = $this->config['db_port'];
        $db_name = $this->config['db_name'];

        // Credentials and (optionally) SSL settings via an option file.
        [$temp, $config_path] = DatabaseSsl::createClientOptionFile($this->config);

        $checkDbExists = new Process\Process(
            [
                'mysql',
                "--defaults-extra-file=$config_path",
                "-P$db_port",
                "-h$db_host",
                "--execute=SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$db_name'"
            ]
        );

        $checkDbExists->run();

        if ($checkDbExists->isSuccessful() && strpos($checkDbExists->getOutput(), $db_name) !== false) {
            $drop = new Process\Process(
                [
                    "mysqladmin",
                    "--defaults-extra-file=$config_path",
                    "-h",
                    "$db_host",
                    "-P",
                    "$db_port",
                    "drop",
                    "$db_name",
                    "--force"
                ]
            );
            $drop->enableOutput();
            $drop->run();
            fclose($temp);
            $message = $drop->getOutput();
            if (!$drop->isSuccessful()) {
                $message = $drop->getErrorOutput();
                $this->output->writeln("<error>$message</error>");
                throw new ProcessFailedException($drop);
            } else {
                if ($this->silent === true) {
                    return 0;
                } else {
                    $this->output->writeln("<info>$message</info>");
                    return 0;
                }
            }
        }
        fclose($temp);
    }
}
