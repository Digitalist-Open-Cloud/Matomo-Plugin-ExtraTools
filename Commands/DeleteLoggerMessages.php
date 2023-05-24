<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Db;
use Piwik\Common;

class DeleteLoggerMessages extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will delete internal logger messages in the database.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('logger:delete');
        $this->setDescription('Delete internal logger messages in database (monolog)');
        $this->addNoValueOption(
            'force',
            null,
            'force removing logs, without confirmation.',
            null
        );
    }

    /**
     * List users.
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $force = $input->getOption('force');
        if ($force === false) {
            $question = $this->askForConfirmation('Are you really sure you would like to delete all logs? ', false);
            if (!$question) {
                $output->writeln("<info>Logs not deleted.</info>");
                return self::FAILURE;
            } else {
                $force = true;
            }
        }
        if ($force === true) {
            try {
                Db::query('TRUNCATE ' . Common::prefixTable('logger_message'));
                $output->writeln("<info>Logs deleted.</info>");
                return self::SUCCESS;
            } catch (\Exception $e) {
                return self::FAILURE;
            }
        }
        return self::SUCCESS;
    }
}
