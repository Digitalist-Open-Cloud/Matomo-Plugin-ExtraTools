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

/**
 * List sites.
 */
class AddUrl extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list all sites you have.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:url');
        $this->setDescription('Add an URL to a site.');
        $this->addOptionalValueOption(
            'id',
            null,
            'Site id to add URL to',
            null
        );
        $this->addOptionalValueOption(
            'url',
            null,
            'URL(s) to add, comma separated, no space',
            null
        );
    }

    /**
     * Execute the command like: ./console site:list"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $id = $input->getOption('id');
        $url = $input->getOption('url');
        if (!$id) {
            $output->writeln("<info>You must provide an id for the site to add URL for</info>");
            return self::FAILURE;
        }
        if (!$url) {
            $output->writeln("'<info>You must provide an URL for the site</info>'");
            return self::FAILURE;
        }

        $urls = explode(",", trim($url));
        $site = new Site($id);
        try {
            $site->addURL($id, $urls);
        } catch (\Exception $e) {
            $output->writeln("<error>The URL could not be added for site $id: {$e->getMessage()}</error>");
            return self::FAILURE;
        }
        $output->writeln("<info>URL $url added for site $id</info>");
        return self::SUCCESS;
    }
}
