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
class DeleteSite extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will delete a site.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:delete');
        $this->setDescription('Delete site.');
        $this->addOptionalValueOption(
            'id',
            'i',
            'Site id to delete',
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
        if (!$id) {
            $output->writeln("<info>You must provide an id for the site to delete</info>");
            return self::FAILURE;
        }
        $id = (int) $id;
        $site = new Site($id);
        $record = $site->record();
        if (!$record) {
            $output->writeln("<info>Site with id <comment>$id</comment> could not be found</info>");
        } else {
            if ($site->totalSites() === 1) {
                $output->writeln("<info>You can't delete the site, you must have at least on site in Matomo.</info>");
                return self::FAILURE;
            }

            $question = $this->askForConfirmation("Are you really sure you would like to delete site $record? ", false);
            if (!$question) {
                $output->writeln("<info>Site was <comment>not</comment> deleted</info>");
                return self::FAILURE;
            } else {
                $delete = $site->delete();
                $output->writeln("<info>Site <comment>$record</comment> deleted</info>");
            }
        }
        return self::SUCCESS;
    }
}
