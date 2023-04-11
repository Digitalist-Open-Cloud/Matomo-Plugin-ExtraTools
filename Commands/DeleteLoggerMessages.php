<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://digitalist.se/contributing-matomo
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Db;
use Piwik\Common;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'force removing logs, without confirmation.',
            null
        );
    }

    /**
     * List users.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        if ($force === false) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you really sure you would like to delete all logs? ', false);
            if (!$helper->ask($input, $output, $question)) {
                return false;
            } else {
                $force = true;
            }
        }
        if ($force === true) {
            try {
                Db::query('TRUNCATE ' . Common::prefixTable('logger_message'));
                $output->writeln("<info>Logs deleted.</info>");
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return 0;
    }
}
