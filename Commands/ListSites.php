<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\ExtraTools\Lib\Site;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\SitesManager\API as APISitesManager;

use Piwik\Plugins\ExtraTools\Lib\Drop;

class ListSites extends ConsoleCommand
{

    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list all sites you have.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:list');
        $this->setDescription('List sites.');
    }

    /**
     * Execute the command like: ./console backup:db"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = new Site(null);
        $sites = $list->list();
        foreach ($sites as $site) {
            $id = $site['idsite'];
        }
    }
}
