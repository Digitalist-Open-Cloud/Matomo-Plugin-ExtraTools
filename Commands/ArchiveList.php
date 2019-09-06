<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\CronArchive\SharedSiteIds  as ListArchives;
use Piwik\Config;

use Piwik\Plugins\ExtraTools\Lib\Backup;

/**
 * This class lets you define a new command. To read more about commands have a look at our Piwik Console guide on
 * http://developer.piwik.org/guides/piwik-on-the-command-line
 *
 * As Piwik Console is based on the Symfony Console you might also want to have a look at
 * http://symfony.com/doc/current/components/console/index.html
 */
class ArchiveList extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will backup your db.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>
You could use options to override config or environment variables:
<info>%command.name% --db-backup-path=/tmp/foo</info>';
        $this->setHelp($HelpText);
        $this->setName('archive:list');
        $this->setDescription('List running archivers (does not work yet)');
        $this->setDefinition(
            [
                new InputOption(
                    'backup-path',
                    'b',
                    InputOption::VALUE_OPTIONAL,
                    'backup path',
                    null
                ),
                new InputOption(
                    'backup-prefix',
                    'p',
                    InputOption::VALUE_OPTIONAL,
                    'prefix for backup name',
                    'backup'
                )
            ]
        );
    }

    /**
     * Execute the command like: ./console backup:db"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = [];
        $list = new ListArchives($ids);
        echo $list->getNumProcessedWebsites();
    }
}
