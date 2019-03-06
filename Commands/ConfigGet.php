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
use Piwik\Config;
use Symfony\Component\Yaml\Yaml;

/**
 * Get config for a section.
 */
class ConfigGet extends ConsoleCommand
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
        $this->setName('config:get');
        $this->setDescription('Get config in the file config/config.ini.php');
        $this->setDefinition(
            [
                new InputOption(
                    'section',
                    's',
                    InputOption::VALUE_REQUIRED,
                    'Section in ini file, like database',
                    null
                ),
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
     * Execute the command like: ./console backup:db"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section = $input->getOption('section');
        $format = $input->getOption('format');

        $configs = Config::getInstance();
        $get_section = $configs->getFromLocalConfig("$section");
        if ($get_section == null) {
            $output->writeln("<info>Looks like section <comment>$section</comment> does not exist</info>");
        } else {
            if ($format=='json') {
                $this->json($get_section);
            }
            if ($format=='yaml') {
                $this->yaml($get_section);
            }
            if ($format=='text') {
                $this->text($get_section, $output);
            }
        }
    }
    private function json($config)
    {
        $json = json_encode($config, JSON_PRETTY_PRINT |JSON_UNESCAPED_SLASHES);
        print_r($json);
        echo "\n";
    }
    private function yaml($config)
    {
        $yaml = Yaml::dump($config, 2, 2);
        print_r($yaml);
    }


    private function text($config, OutputInterface $output)
    {

        foreach ($config as $key => $section) {
            if (is_array($section)) {
                foreach ($section as $key_1 => $section_1) {
                    $output->write("<info>$key: </info>");
                    $output->writeln("<info><comment>$section_1</comment></info>");
                }
            } else {
                $output->writeln("<info>$key: <comment>$section</comment></info>");
            }
        }
    }
}
