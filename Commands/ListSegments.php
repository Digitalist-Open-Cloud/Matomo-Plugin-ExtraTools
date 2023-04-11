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
        return 0;
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
