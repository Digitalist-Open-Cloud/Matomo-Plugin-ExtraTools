<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\SegmentEditor\Model as SegmentEditorModel;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class ListSegments extends ConsoleCommand
{
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $segments = $this->getSegments();

        foreach ($segments as $out) {
            if ($out['enable_only_idsite'] === '0') {
                $enabled =  "Enabled for: <comment>all sites</comment>";
            } else {
                $enabled = "Enabled for site id: <comment>" . $out['enable_only_idsite'] . "</comment>";
            }
            $auto_archive = '';
            if ($out['auto_archive'] === '0') {
                $auto_archive = 'Segment are processed in realtime';
            } elseif ($out['auto_archive'] === '1') {
                $auto_archive = 'Segment are pre-processed (cron)';
            } elseif ($out['auto_archive'] === '2') {
                $auto_archive = 'Segment are not processed (paused)';
            }

            $message= "Segment ID: <comment>" . $out['idsegment'] . "</comment>\n"
                . "     Name: <comment>" . $out['name']. "</comment>\n"
            . "     Definition: <comment>" . $out['definition']. "</comment>\n"
                . "     URL encoded definition: <comment>" . urlencode($out['definition']). "</comment>\n"
            . "     Created: <comment>" . $out['ts_created']. "</comment>\n"
            . "     $enabled\n"
            . "     $auto_archive\n";
            if (isset($out['ts_last_edit'])) {
                $message .=  "     Latest update: <comment>" . $out['ts_last_edit']. "</comment>";
            }

            $output->writeln("<info>$message</info>");
        }
    }

    /**
     * @param int[] $idSegments
     * @return array
     */
    public function getSegments()
    {
        /** @var SegmentEditorModel $segmentEditorModel */
        $segmentEditorModel = StaticContainer::get('Piwik\Plugins\SegmentEditor\Model');
        $segments = $segmentEditorModel->getAllSegmentsAndIgnoreVisibility();

        return $segments;
    }
}
