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
        $this->addOptionalValueOption(
            'id',
            'i',
            'Site id to delete',
            null
        );
    }

    /**
     * Execute the command like: ./console site:list"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $id = $input->getOption('id');
        $site = new Site($id);
        $record = $site->record();
        if (!$id) {
            $output->writeln("<info>You must provide an id for the site to delete</info>");
            return self::FAILURE;
        }
        if (!$record) {
            $output->writeln("<info>Site with id <comment>$id</comment> could not be found</info>");
        } else {
            if ($site->totalSites() === 1) {
                $output->writeln("<info>You can't delete the site, you must have at least on site in Matomo.</info>");
                return self::FAILURE;
            }

            $question = $this->askForConfirmation("Are you really sure you would like to delete site $record? ", false);
            if (!$question) {
                $output->writeln("<info>Site was <comment>not</comment> deleted</info>");
                return self::FAILURE;
            } else {
                $delete = $site->delete();
                $output->writeln("<info>Site <comment>$record</comment> deleted</info>");
            }
        }
        return self::SUCCESS;
    }
}
