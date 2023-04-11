<?php

/**
 * ExtraTools
 *
 * @link https://github.com/digitalist-se/extratools
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\ExtraTools\Lib\Archivers;
use Piwik\Common;
use Piwik\Db;

class ArchiveList extends ConsoleCommand
{
    private static $rawPrefix = 'segment';

    protected function getTable()
    {
        return Common::prefixTable(self::$rawPrefix);
    }

    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> wil list archives that are going
        or beeing archived.';
        $this->setHelp($HelpText);
        $this->setName('archive:list');
        $this->setDescription('List archivers listed for archivation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $get = $this->getArchivers();
        if (!empty($get)) {
            foreach ($get as $out) {
                $name = $out['name'];
                if ($name == 'done') {
                    $out_name = '';
                } else {
                    $hash = preg_replace('/^done/', '', $name);
                    $name = $this->getSegmentName($hash);
                    $out_name =  "     segment: <comment>" . $name['name'] . "</comment>\n";
                }
                $out_period = '';
                if (isset($out['period'])) {
                    $period = $out['period'];
                    switch ($period) {
                        case 1:
                            $period = 'day';
                            break;
                        case 2:
                            $period = 'week';
                            break;
                        case 3:
                            $period = 'month';
                            break;
                        case 4:
                            $period = 'year';
                            break;
                    }
                    $out_period =  "     period: <comment>" . $period . "</comment>\n";
                }
                if (isset($out['started'])) {
                    $started = "     started: <comment>" . $out['started'] . "</comment>\n";
                } else {
                    $started = "     started: <comment>no</comment>\n";
                }
                $message = "idsite: <comment>" . $out['idsite'] . "</comment>\n"
                . $out_name
                . "     from: <comment>" . $out['date1'] . "</comment>\n"
                . "     to: <comment>" . $out['date2'] . "</comment>\n"
                . $out_period
                . "     invalidated: <comment>" . $out['ts_invalidated'] . "</comment>\n"
                . $started;
                $output->writeln("<info>$message</info>");
            }
        } else {
            $output->writeln("<info>no archivers ongoing or scheduled</info>");
        }
        return 0;
    }

    public function getArchivers()
    {
        $list = new Archivers();
        $out = $list->getAllInvalidations();
        return $out;
    }


    public function getSegmentName($hash)
    {
        try {
            $sql = "SELECT `name` FROM " . $this->getTable() . " WHERE `hash` = '" . $hash . "';";
            $name = $this->getDb()->fetchRow($sql);
            return $name;
        } catch (\Exception $e) {
            return false;
        }
    }
    private function getDb()
    {
        return Db::get();
    }
}
