<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\SitesManager\SitesManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\SegmentEditor\Model as SegmentEditorModel;
use Symfony\Component\Console\Input\InputOption;
use Piwik\API\Request;
use Piwik\Report;
use Piwik\Plugins\API\API;
use Piwik\Plugins\Live\Model;
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
        $this->setDefinition(
            [
                new InputOption(
                    'id',
                    'i',
                    InputOption::VALUE_OPTIONAL,
                    'Site id to list total visits for.',
                    null
                ),
                new InputOption(
                    'period',
                    'p',
                    InputOption::VALUE_OPTIONAL,
                    'Period to use - day, week, month, year. Defaults to day.',
                    'day'
                ),
                new InputOption(
                    'date',
                    'd',
                    InputOption::VALUE_OPTIONAL,
                    'Matomo date to get visits from. today, yesterday, 2020-04-12. You could also use range: '
                    . '2020-03-30,2020-04-20. Defaults to today.',
                    'today'
                ),
                new InputOption(
                    'segmentid',
                    's',
                    InputOption::VALUE_OPTIONAL,
                    'Segment id, to get total visits in a segment.',
                    null
                ),
                new InputOption(
                    'view',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'What to output, visits, actions, pageviews, revenue,  defaults to pageviews',
                    'pageviews'
                ),
            ]
        );
    }

    /**
     * List visits
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

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
        return 0;
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
