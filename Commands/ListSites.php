<?php

/**
 * ExtraTools
 *
 * @link https://github.com/digitalist-se/extratools
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\ExtraTools\Lib\Site;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * List sites.
 */
class ListSites extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will list all sites.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:list');
        $this->setDescription('List sites.');
        $this->setDefinition(
            [
                new InputOption(
                    'format',
                    'f',
                    InputOption::VALUE_OPTIONAL,
                    'Output format (json, yaml, text)',
                    'text'
                )
            ]
        );
    }

    /**
     * Execute the command like: ./console site:list"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $format = $input->getOption('format');

        $list = new Site(null);
        $sites = $list->list();
        foreach ($sites as $site) {
            $outsites[] = [
                "id" => $site['idsite'],
                "name" => $site['name'],
                "created" => $site['ts_created'],
                "main-url" => $site['main_url'],
                "timezone" => $site['timezone'],
                "type" => $site['type']
            ];
        }

        if (isset($outsites)) {
            if ($format == 'json') {
                $this->json($outsites);
            }
            if ($format == 'yaml') {
                $this->yaml($outsites);
            }
            if ($format == 'text') {
                $this->text($outsites, $output);
            }
            return self::SUCCESS;
        } else {
            $output->write("<info>No sites in Matomo</info>");
            return self::FAILURE;
        }
    }
    private function json($sites)
    {
        $json = json_encode($sites, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        print_r($json);
        echo "\n";
    }
    private function yaml($sites)
    {
        $yaml = Yaml::dump($sites, 3, 2);
        print_r($yaml);
    }


    private function text($sites, OutputInterface $output)
    {
        foreach ($sites as $key => $site) {
            foreach ($site as $key => $site) {
                $output->write("<info>$key: </info>");
                $output->writeln("<info><comment>$site</comment></info>");
            }
            $output->writeln("<info>*****</info>");
        }
    }
}
