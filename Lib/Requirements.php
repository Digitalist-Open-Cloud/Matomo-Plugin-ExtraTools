<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\Diagnostics\DiagnosticService;
use Piwik\SettingsPiwik;
use Symfony\Component\Console\Helper\Table;

class Requirements
{

    /**
     * @var OutputInterface
     */
    private $output;


    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function execute()
    {
        $this->output->writeln("<info>" . $this->checkMatomoIsInstalled() . "</info>");
        $this->runDiagnostics($this->output);
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

        $this->output->writeln("<info>Mandatory test results</info>");

        $results = $diagnosticReport->getMandatoryDiagnosticResults();
        $errors =  $diagnosticReport->getErrorCount();
        $warnings =  $diagnosticReport->getWarningCount();

        $table = new Table($this->output);
        $table->setHeaders(['Test', 'Result', 'Output']);
        foreach ($results as $result) {
            foreach ($result->getItems() as $item) {
                $rows[] = [$result->getLabel(), $item->getStatus(), wordwrap($item->getComment(), 40, "\n")];
            }
        }
        $table->setRows($rows);
        $table->render();

        $this->output->writeln("<info>Optional test results</info>");

        $results = $diagnosticReport->getOptionalDiagnosticResults();

        $table = new Table($output);

        $table->setHeaders(['Test', 'Result', 'Output']);
        foreach ($results as $result) {
            foreach ($result->getItems() as $item) {
                $rows[] = [$result->getLabel(), $item->getStatus(), wordwrap($item->getComment(), 40, "\n")];
            }
        }
        $table->setRows($rows);
        $table->render();

        if ($diagnosticReport->hasErrors()) {
            $output->writeln("<error>Errors were found in!</error>");
        }

        $this->output->writeln("<info>Number of type errors: <comment>$errors</comment>. Number of type warnings: <comment>$warnings</comment></info>");
    }

    /**
     * @return bool
     * @throws \DI\NotFoundException
     *
     */
    public function hasErrors()
    {
        /** @var DiagnosticService $diagnosticService */
        $diagnosticService = StaticContainer::get('Piwik\Plugins\Diagnostics\DiagnosticService');
        $diagnosticReport = $diagnosticService->runDiagnostics();
        return $diagnosticReport->hasErrors();
    }
}
