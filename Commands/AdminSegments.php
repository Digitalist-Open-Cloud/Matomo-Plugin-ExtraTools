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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\SegmentEditor\Model as SegmentEditorModel;
use Piwik\Common;
use Piwik\Db;
use Piwik\Date;

/**
 * Class AdminSegments
 * @package Piwik\Plugins\ExtraTools\Commands
 */
class AdminSegments extends ConsoleCommand
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
        $this->setName('segment:admin');
        $this->setDescription('Administrate segments');
        $this->setDefinition(
            [
                new InputOption(
                    'delete-segment',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Name for the site',
                    null
                ),
                new InputOption(
                    'activate-segment',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Name for the site',
                    null
                ),
            ]
        );
    }

    /**
     * List users.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $deleteSegment = $input->getOption('delete-segment');
        $activateSegment = $input->getOption('activate-segment');
        $info = '';

        if (isset($deleteSegment)) {
            $segmentId = $deleteSegment;
        }
        if (isset($activateSegment)) {
            $segmentId = $activateSegment;
        }
        if (!isset($segmentId)) {
            $output->writeln("<error>You need to provide a segment id</error>");
            return exit(1);
        }
        $validate = $this->isInt($segmentId);
        if (!$validate) {
            $output->writeln("<error>You need to provide a single int id for segment (ex. 1)</error>");
        }

        $segment = $this->getSegment($segmentId);

        if (!$segment) {
            $output->writeln("<error>You need to provide an existing segment id</error>");
            return exit(1);
        }

        if (isset($deleteSegment)) {
            $this->deleteSegment($segmentId);
            $info = "Segment id $segmentId marked as deleted";
        }

        if (isset($activateSegment)) {
            $this->unDeleteSegment($segmentId);
            $info = "Segment id $segmentId is now active";
        }


        $output->writeln("<info>$info</info>");
        return 0;
    }


    /**
     * @param $idSegment
     * @return array
     * @throws \Exception
     */
    public function getSegment($idSegment)
    {
        $db = $this->getDb();
        $segment = $db->fetchRow("SELECT * FROM " . $this->getTable() . " WHERE idsegment = ?", $idSegment);

        return $segment;
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
     * @param $idSegment
     * @throws \Exception
     */
    public function deleteSegment($idSegment)
    {
        $fieldsToSet = array(
            'deleted' => 1,
            'ts_last_edit' => Date::factory('now')->toString('Y-m-d H:i:s')
        );

        $db = $this->getDb();
        $db->update($this->getTable(), $fieldsToSet, 'idsegment = ' . (int) $idSegment);
    }

    /**
     * @param $idSegment
     * @throws \Exception
     */
    public function unDeleteSegment($idSegment)
    {
        $fieldsToSet = array(
            'deleted' => 0,
            'ts_last_edit' => Date::factory('now')->toString('Y-m-d H:i:s')
        );

        $db = $this->getDb();
        $db->update($this->getTable(), $fieldsToSet, 'idsegment = ' . (int) $idSegment);
    }

    /**
     * @param $idSegment
     * @return bool
     */
    private function isInt($idSegment)
    {
        if (filter_var($idSegment, FILTER_VALIDATE_INT)) {
            return true;
        } else {
            return false;
        }
    }


    private function getDb()
    {
        return Db::get();
    }
}
