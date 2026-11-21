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
use Piwik\Container\StaticContainer;
use Piwik\Plugins\SegmentEditor\Model as SegmentEditorModel;
use Piwik\Plugins\API\API;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class GetVisits extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list total visits.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('visits:get');
        $this->setDescription('Gets archived visits for all or a chosen site.');
        $this->addOptionalValueOption('id', 'i', 'Site id to list total visits for.', null);
        $this->addOptionalValueOption('period', 'p', 'Period to use - day, week, month, year. Defaults to day.', 'day');
        $this->addOptionalValueOption('date', 'd', 'Matomo date to get visits from. today, yesterday, 2020-04-12. You could also use range: '
            . '2020-03-30,2020-04-20. Defaults to today.', 'today');
        $this->addOptionalValueOption('segmentid', 's', 'Segment id, to get total visits in a segment.', null);
        $this->addOptionalValueOption('view', null, 'What to output, visits, actions, pageviews, revenue,  defaults to pageviews', 'pageviews');
    }

    /**
     * List visits
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $single = $input->getOption('id');
        $period = $input->getOption('period');
        $date = $input->getOption('date');
        $view = 'nb_' . $input->getOption('view');
        if ($view == 'nb_revenue') {
            $view = 'revenue';
        }

        $siteIds = SitesManagerAPI::getInstance()->getAllSitesId();
        $id = $siteIds['0'];
        if (isset($single)) {
            if (!in_array($single, $siteIds)) {
                $output->writeln('site id looks to be invalid.');
                return 1;
            }
        }
        $segmentid = $input->getOption('segmentid');
        $definition = null;
        if (isset($segmentid)) {
            if ($this->getSegmentDefinition($segmentid)) {
                $definition = $this->getSegmentDefinition($segmentid);
            }
        }
        if (isset($single)) {
            $api = API::getInstance()->getProcessedReport($single, $period, $date, 'MultiSites', 'getOne', $definition);
            $total = $api['reportTotal'][$view];
        } else {
            $api = API::getInstance()->getProcessedReport($id, $period, $date, 'MultiSites', 'getAll', $definition);
            $total = $api['reportTotal'][$view];
        }
        if (!(isset($total))) {
            $total = "looks like you have no archived visits";
        }
        $output->writeln("Total $view $total");
        return self::SUCCESS;
    }

    public function getSegmentName($segmentid)
    {
        /** @var SegmentEditorModel $segmentEditorModel */
        $segmentEditorModel = StaticContainer::get('Piwik\Plugins\SegmentEditor\Model');
        try {
            return $segmentEditorModel->getSegment($segmentid);
        } catch (\Exception $e) {
            return false;
        }
    }
    public function getSegmentDefinition($segmentid)
    {

        try {
            $segment = $this->getSegmentName($segmentid);
        } catch (\Exception $e) {
            return false;
        }
        if (isset($segment)) {
            return urlencode($segment['definition']);
        }
    }
}
