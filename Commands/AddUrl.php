<?php

/**
 * ExtraTools
 *
 * @link https://github.com/digitalist-se/extratools
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or laster
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\ExtraTools\Lib\Site;

/**
 * List sites.
 */
class AddUrl extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list all sites you have.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:url');
        $this->setDescription('Add an URL to a site.');
        $this->addOptionalValueOption(
            'id',
            null,
            'Site id to add URL to',
            null
        );
        $this->addOptionalValueOption(
            'url',
            null,
            'URL(s) to add, comma separated, no space',
            null
        );
    }

    /**
     * Execute the command like: ./console site:list"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $id = $input->getOption('id');
        $url = $input->getOption('url');
        if (!$id) {
            $output->writeln("<info>You must provide an id for the site to add URL for</info>");
            return self::FAILURE;
        }
        if (!$url) {
            $output->writeln("'<info>You must provide an URL for the site</info>'");
            return self::FAILURE;
        }

        $urls = explode(",", trim($url));
        $site = new Site($id);
        $site->addURL($id, $urls);
        $output->writeln("<info>URL $url added for site $id</info>");
        return self::SUCCESS;
    }
}
