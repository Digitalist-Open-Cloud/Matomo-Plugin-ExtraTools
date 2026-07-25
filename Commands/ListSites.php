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
use Piwik\Plugins\ExtraTools\Lib\Site;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * List sites.
 */
class ListSites extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list all sites.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:list');
        $this->setDescription('List sites.');
        $this->setDefinition(
            [
                new InputOption(
                    'format',
                    'f',
                    InputOption::VALUE_OPTIONAL,
                    'Output format (json, yaml, text)',
                    'text'
                )
            ]
        );
    }

    /**
     * Execute the command like: ./console site:list"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $format = $input->getOption('format');

        $list = new Site(null);
        $sites = $list->list();
        foreach ($sites as $site) {
            $outsites[] = [
                "id" => $site['idsite'],
                "name" => $site['name'],
                "created" => $site['ts_created'],
                "main-url" => $site['main_url'],
                "timezone" => $site['timezone'],
                "currency" => $site['currency'],
                "type" => $site['type']
            ];
        }

        if (isset($outsites)) {
            match ($format) {
                'json' => $this->json($outsites),
                'yaml' => $this->yaml($outsites),
                'text' => $this->text($outsites, $output),
                default => null,
            };
            return self::SUCCESS;
        } else {
            $output->write("<info>No sites in Matomo</info>");
            return self::FAILURE;
        }
    }
    private function json($sites)
    {
        $json = json_encode($sites, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        print_r($json);
        echo "\n";
    }
    private function yaml($sites)
    {
        $yaml = Yaml::dump($sites, 3, 2);
        print_r($yaml);
    }


    private function text($sites, OutputInterface $output)
    {
        foreach ($sites as $key => $site) {
            foreach ($site as $key => $site) {
                $output->write("<info>$key: </info>");
                $output->writeln("<info><comment>$site</comment></info>");
            }
            $output->writeln("<info>*****</info>");
        }
    }
}
