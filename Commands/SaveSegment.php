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

use Piwik\Access;
use Piwik\Common;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\SegmentEditor\API;
use Piwik\Plugins\SegmentEditor\Model;
use Piwik\Site;

/**
 * Adds a segment, or changes one when the option --id is given.
 */
class SaveSegment extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('segment:save');
        $this->setDescription('Add a segment, or change one with --id');
        $this->setHelp('To add a segment for all sites, seen by all users:
<info>%command.name% --name="Swedish Firefox" --definition="browserCode==FF;countryCode==se"</info>
To change the name of segment 3 and leave the rest as it is:
<info>%command.name% --id=3 --name="Firefox in Sweden"</info>');
        $this->addRequiredValueOption('id', null, 'ID of the segment to change. Without it a segment is added');
        $this->addRequiredValueOption('name', null, 'Name of the segment');
        $this->addRequiredValueOption('definition', null, 'Definition of the segment, as in the segment parameter of the API');
        $this->addRequiredValueOption('site', null, 'ID of the only site the segment is for, or "all" (default when adding)');
        $this->addRequiredValueOption('auto-archive', null, 'true to process the segment when archiving, false to process it when it is asked for (default when adding)');
    }

    protected function doExecute(): int
    {
        try {
            $message = Access::doAsSuperUser(fn () => $this->save());
        } catch (\Exception $e) {
            $this->getOutput()->writeln('<error>' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }

        $this->getOutput()->writeln("<info>$message</info>");
        return self::SUCCESS;
    }

    private function save(): string
    {
        $input = $this->getInput();
        $id = $input->getOption('id');
        $name = $input->getOption('name');
        $definition = $input->getOption('definition');
        $site = $input->getOption('site');
        $autoArchive = $input->getOption('auto-archive');

        if ($autoArchive !== null && !in_array($autoArchive, ['true', 'false'], true)) {
            throw new \InvalidArgumentException('The option --auto-archive has to be true or false.');
        }
        if ($site !== null && $site !== 'all' && filter_var($site, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('The option --site has to be the ID of a site, or all.');
        }
        $idSite = $site === null || $site === 'all' ? null : (int) $site;
        if ($idSite !== null) {
            // Fails for a site that does not exist, which Matomo accepts from a super user.
            new Site($idSite);
        }

        if ($id === null) {
            if ((string) $name === '' || (string) $definition === '') {
                throw new \InvalidArgumentException('A new segment needs the options --name and --definition.');
            }
            // Seen by all users: the command line has no user that could own the segment.
            $id = API::getInstance()->add($name, $definition, $idSite, $autoArchive === 'true', true);

            return "Segment $name added with ID $id";
        }

        $segment = filter_var($id, FILTER_VALIDATE_INT) === false ? null : (new Model())->getSegment((int) $id);
        if (!$segment) {
            throw new \InvalidArgumentException("There is no segment with ID $id.");
        }
        if ($name === null && $definition === null && $site === null && $autoArchive === null) {
            throw new \InvalidArgumentException('Give at least one of the options --name, --definition, --site and --auto-archive.');
        }

        API::getInstance()->update(
            (int) $id,
            // What is stored is encoded, and Matomo encodes what it is given.
            $name ?? Common::unsanitizeInputValue($segment['name']),
            $definition ?? Common::unsanitizeInputValue($segment['definition']),
            $site === null ? ((int) $segment['enable_only_idsite'] ?: null) : $idSite,
            $autoArchive === null ? (bool) $segment['auto_archive'] : $autoArchive === 'true',
            (bool) $segment['enable_all_users']
        );

        return "Segment $id updated";
    }
}
