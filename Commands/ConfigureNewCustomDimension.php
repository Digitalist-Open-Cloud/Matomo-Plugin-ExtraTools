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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Config;
use Piwik\Plugins\CustomDimensions\API as CustomDimensionsAPI;
use Exception;

class ConfigureNewCustomDimension extends ConsoleCommand
{
    /**
     * This methods allows you to configure your command. Here you can define the name and description of your command
     * as well as all options and arguments you expect when executing it.
     */
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command will configure a new CustomDimension.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('customdimensions:configure-new-dimension');
        $this->setDescription('Configure new Custom Dimension');
        $this->setDefinition(
            [
                new InputOption(
                    'id',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'Site id',
                    null
                ),
                new InputOption(
                    'name',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'Name of the custom dimension',
                    null
                ),
                new InputOption(
                    'scope',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'Scope - visit or action',
                    null
                ),
                new InputOption(
                    'active',
                    '',
                    InputOption::VALUE_NONE,
                    'If provided, the custom dimension is marked as active',
                    null
                )
            ]
        );
    }

    /**
     * Execute the command like.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $idsite = $input->getOption('id');
        $name = $input->getOption('name');
        $scope = $input->getOption('scope');
        $active = $input->getOption('active') ? true : false;

        $configure = $this->configureCustomDimension($idsite, $name, $scope, $active);
        $output->writeln('<info>Adding</info>');
        return 0;
    }
    public function configureCustomDimension($idsite, $name, $scope, $active)
    {
        $configureNew = new CustomDimensionsAPI();
        try {
            $add = $configureNew->configureNewCustomDimension($idsite, $name, $scope, $active);
            return 0;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return 1;
        }
    }
}
