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
use Piwik\Common;
use Piwik\Db;

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $segments = $this->getAllSegments();

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
            $auto_archive = '';
            if ($out['auto_archive'] === '0') {
                $auto_archive = 'Segment is processed in realtime';
            } elseif ($out['auto_archive'] === '1') {
                $auto_archive = 'Segment is pre-processed (cron)';
            } elseif ($out['auto_archive'] === '9') {
                $auto_archive = 'Segment is not processed (paused)';
            }

            $message= "Segment ID: <comment>" . $out['idsegment'] . "</comment>\n"
                . "     Name: <comment>" . $out['name']. "</comment>\n"
            . "     Definition: <comment>" . $out['definition']. "</comment>\n"
                . "     URL encoded definition: <comment>" . urlencode($out['definition']). "</comment>\n"
            . "     Created: <comment>" . $out['ts_created']. "</comment>\n"
            . "     $enabled\n"
            . "     $auto_archive\n"
            . "     $deleted\n";
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
    /**
     * Returns all stored segments that haven't been deleted. Ignores the site the segments are enabled
     * for and whether to auto archive or not.
     *
     * @return array
     */
    public function getAllSegments()
    {
        $sql = "SELECT * FROM " . $this->getTable();

        $segments = $this->getDb()->fetchAll($sql);

        return $segments;
    }
    private function getDb()
    {
        return Db::get();
    }
}
