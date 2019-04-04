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
use Piwik\Plugins\ExtraTools\Lib\Requirements;

if (file_exists(PIWIK_DOCUMENT_ROOT . '/bootstrap.php')) {
    require_once PIWIK_DOCUMENT_ROOT . '/bootstrap.php';
}

/**
 * Get config for a section.
 */
class RequirementsMatomo extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> will check for Matomo depencies.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('matomo:requirements');
        $this->setDescription('Check Matomo requirements');
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
     * Execute the command like: ./console backup:db"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $check = new Requirements($output);
        $check->execute();
    }
}
