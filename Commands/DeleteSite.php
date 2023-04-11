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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * List sites.
 */
class DeleteSite extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> will delete a site.
<comment>Samples:</comment>
To run:
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('site:delete');
        $this->setDescription('Delete site.');
        $this->setDefinition(
            [
                new InputOption(
                    'id',
                    'i',
                    InputOption::VALUE_OPTIONAL,
                    'Site id to delete',
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
        $site = new Site($id);
        $record = $site->record();
        if (!$id) {
            $output->writeln("<info>You must provide an id for the site to delete</info>");
            exit;
        }
        if (!$record) {
            $output->writeln("<info>Site with id <comment>$id</comment> could not be found</info>");
        } else {
            if ($site->totalSites() === 1) {
                $output->writeln("<info>You can't delete the site, you must have at least on site in Matomo.</info>");
                exit;
            }
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("Are you really sure you would like to delete site $record? ", false);
            if (!$helper->ask($input, $output, $question)) {
                return;
            } else {
                $delete = $site->delete();
                if (!$delete) {
                    $output->writeln("<info>Site <comment>$record</comment> could not be deleted</info>");
                } else {
                    $output->writeln("<info>Site <comment>$record</comment> deleted</info>");
                }
            }
        }
        return 0;
    }
}
