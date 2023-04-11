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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

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
        $this->setDefinition(
            [
                new InputOption(
                    'id',
                    'i',
                    InputOption::VALUE_OPTIONAL,
                    'Site id to add URL to',
                    null
                ),
                new InputOption(
                    'url',
                    'u',
                    InputOption::VALUE_OPTIONAL,
                    'URL(s) to add, comma separated, no space',
                    null
                )
            ]
        );
    }

    /**
     * Execute the command like: ./console site:list"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getOption('id');
        $url = $input->getOption('url');

        if (!$id) {
            $output->writeln("<info>You must provide an id for the site to add URL for</info>");
            exit;
        }
        if (!$url) {
            $output->writeln("<info>You must provide an URL for the site</info>");
            exit;
        }

        $urls = explode(",", trim($url));
        $site = new Site($id);
        $site->addURL($id, $urls);
        return 0;
    }
}
