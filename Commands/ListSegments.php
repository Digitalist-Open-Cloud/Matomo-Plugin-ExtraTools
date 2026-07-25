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
use Piwik\Common;
use Piwik\Db;
use Piwik\Config;

/**
 * Class ListSegments
 * @package Piwik\Plugins\ExtraTools\Commands
 */
class ListSegments extends ConsoleCommand
{
    private static $rawPrefix = 'segment';

    protected function getTable()
    {
        return Common::prefixTable(self::$rawPrefix);
    }

    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list att your segments.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('segment:list');
        $this->setDescription('List segments');
    }

    /**
     * List users.
     */
    protected function doExecute(): int
    {
        $output = $this->getOutput();
        $segments = $this->getAllSegments();

        if (!$segments) {
            $output->writeln("<info>There is no segments</info>");
            return self::SUCCESS;
        }

        foreach ($segments as $out) {
            if ($out['deleted'] === '0') {
                $deleted =  "Segment is: <comment>active</comment>";
            } else {
                $deleted =  "Segment is: <comment>deleted</comment>";
            }

            if ($out['enable_only_idsite'] === '0') {
                $enabled =  "Enabled for: <comment>all sites</comment>";
            } else {
                $enabled = "Enabled for site id: <comment>" . $out['enable_only_idsite'] . "</comment>";
            }
            $segment_paused = '';
            // This functionality comes from a patch to Segment Editor
            // https://gist.github.com/mikkeschiren/7a0c6f5b5ce912c0bd7f898be78ac51b
            if (isset(Config::getInstance()->Segments['pause'])) {
                $paused = Config::getInstance()->Segments['pause'];
            }
            if (isset($paused)) {
                trim($paused);
                $id = $out['idsegment'];
                $pausedSegmentIDs = explode(",", $paused);
                if (in_array($id, $pausedSegmentIDs, true)) {
                    $segment_paused = "Segment is paused";
                }
            }

            $auto_archive = '';
            if ($out['auto_archive'] === '0') {
                $auto_archive = 'Segment is processed in realtime';
            } elseif ($out['auto_archive'] === '1') {
                $auto_archive = 'Segment is pre-processed (cron)';
            } elseif ($out['auto_archive'] === '9') {
                $auto_archive = 'Segment is not processed (paused)';
            }

            $message = "Segment ID: <comment>" . $out['idsegment'] . "</comment>\n"
                . "     Name: <comment>" . $out['name'] . "</comment>\n"
            . "     Definition: <comment>" . $out['definition'] . "</comment>\n"
                . "     URL encoded definition: <comment>" . urlencode($out['definition']) . "</comment>\n"
            . "     Created: <comment>" . $out['ts_created'] . "</comment>\n"
            . "     $enabled\n"
            . "     $auto_archive\n"
            . "     $deleted";
            if (isset($out['ts_last_edit'])) {
                $message .=  "\n     Latest update: <comment>" . $out['ts_last_edit'] . "</comment>";
            }
            if (!is_null($segment_paused)) {
                $message .= "\n     $segment_paused\n";
            }

            // Remove double newlines if any strings above is empty.
            $message = preg_replace("/[\n]+/", "\n", $message);

            $output->writeln("<info>$message</info>");
        }
        return self::SUCCESS;
    }

    /**
     * Returns all stored segments that haven't been deleted. Ignores the site the segments are enabled
     * for and whether to auto archive or not.
     *
     * @return array
     */
    public function getAllSegments()
    {
        $sql = "SELECT * FROM " . $this->getTable();

        return Db::get()->fetchAll($sql);
    }
}
