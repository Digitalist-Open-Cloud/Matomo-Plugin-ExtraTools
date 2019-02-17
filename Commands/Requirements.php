<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\MatomoExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Config;
use Symfony\Component\Yaml\Yaml;
use Piwik\Plugin\Dependency;


use Exception;
use Piwik\Access;
use Piwik\AssetManager;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Filesystem;
use Piwik\Http;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugins\Diagnostics\DiagnosticService;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Plugins\SitesManager\API as APISitesManager;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\ProxyHeaders;
use Piwik\SettingsPiwik;
use Piwik\Tracker\TrackerCodeGenerator;
use Piwik\Translation\Translator;
use Piwik\Updater;
use Piwik\Url;
use Piwik\Version;
use Zend_Db_Adapter_Exception;
use Symfony\Component\Console\Helper\Table;

if (file_exists(PIWIK_DOCUMENT_ROOT . '/bootstrap.php')) {
    require_once PIWIK_DOCUMENT_ROOT . '/bootstrap.php';
}

/**
 * Get config for a section.
 */
class Requirements extends ConsoleCommand
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
        echo $this->systemCheck($output);
    }


    private function systemCheck($output)
    {
        $output->writeln("<info>" . $this->checkMatomoIsInstalled() . "</info>");
        $this->runDiagnostics($output);
    }

    private function checkMatomoIsInstalled()
    {
        if (!SettingsPiwik::isMatomoInstalled()) {
            return 'Matomo is not installed';
        } else {
            return 'Matomo is installed';
        }
    }

    private function runDiagnostics($output)
    {
        /** @var DiagnosticService $diagnosticService */
        $diagnosticService = StaticContainer::get('Piwik\Plugins\Diagnostics\DiagnosticService');
        $diagnosticReport = $diagnosticService->runDiagnostics();
        if ($diagnosticReport->hasErrors()) {
            return "Errors were found";
        }

        $output->writeln("<info>Mandatory results</info>");

        $results = $diagnosticReport->getMandatoryDiagnosticResults();
        $errors =  $diagnosticReport->getErrorCount();
        $warnings =  $diagnosticReport->getWarningCount();

        $table = new Table($output);
        $table->setColumnWidth(0, 20);
        $table->setColumnWidth(1, 8);
        $table->setColumnWidth(2, 40);

        $table->setHeaders(['Test', 'Result', 'Output']);
        foreach ($results as $result) {
            foreach ($result->getItems() as $item) {
                $rows[] = [$result->getLabel(), $item->getStatus(), $item->getComment()];
            }
        }
        $table->setRows($rows);
        $table->render();

        $output->writeln("<info>Optional results</info>");

        $results = $diagnosticReport->getOptionalDiagnosticResults();
        $errors =  $diagnosticReport->getErrorCount();
        $warnings =  $diagnosticReport->getWarningCount();

        $table = new Table($output);
        $table->setColumnWidth(0, 20);
        $table->setColumnWidth(1, 8);
        $table->setColumnWidth(2, 40);
        $table->setHeaders(['Test', 'Result', 'Output']);
        foreach ($results as $result) {
            foreach ($result->getItems() as $item) {
                $rows[] = [$result->getLabel(), $item->getStatus(), wordwrap($item->getComment(), 40, "\n")];
            }
        }
        $table->setRows($rows);
        $table->render();




         $output->writeln("<info>Number of errors: <comment>$errors</comment>. Number of warnings: <comment>$warnings</comment></info>");
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
