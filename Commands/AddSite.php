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

use Piwik\Plugins\ExtraTools\Lib\Site;

class AddSite extends ConsoleCommand
{

    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will add new site.
<comment>Samples:</comment>
To run:
<info>%command.name% --name=Foo --urls=https://foo.bar</info>
You could use options to override config or environment variables:
<info>%command.name% --db-backup-path=/tmp/foo</info>';
        $this->setHelp($HelpText);
        $this->setName('site:add');
        $this->setDescription('Add a new site');
        $this->setDefinition(
            [
                new InputOption(
                    'name',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Name for the site',
                    null
                ),
                new InputOption(
                    'urls',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'URL for the site',
                    null
                ),
                new InputOption(
                    'ecommerce',
                    null,
                    InputOption::VALUE_NONE,
                    'If the site is a ecommerce site',
                    null
                ),
                new InputOption(
                    'no-site-search',
                    null,
                    InputOption::VALUE_NONE,
                    'If site search should be tracked',
                    null
                ),
                new InputOption(
                    'search-keyword-parameters',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Search keyword parameters',
                    null
                ),
                new InputOption(
                    'search-category-parameters',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Search category parameters',
                    null
                ),
                new InputOption(
                    'exclude-ips',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Exclude IPs',
                    null
                ),
                new InputOption(
                    'exclude-query-parameters',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Exclude query parameters',
                    null
                ),
                new InputOption(
                    'timezone',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'TImezone',
                    null
                ),
                new InputOption(
                    'currency',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Currency',
                    null
                ),
                new InputOption(
                    'group',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Group',
                    null
                ),
                new InputOption(
                    'start-date',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Start date',
                    null
                ),
                new InputOption(
                    'exclude-user-agents',
                    null,
                    InputOption::VALUE_NONE,
                    'Exclude user agents',
                    null
                ),
                new InputOption(
                    'keep-url-fragments',
                    null,
                    InputOption::VALUE_NONE,
                    'Keep url fragments',
                    null
                ),
                new InputOption(
                    'type',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Type',
                    null
                ),
                new InputOption(
                    'settings-value',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Settings value',
                    null
                ),
                new InputOption(
                    'exclude-unknown-urls',
                    null,
                    InputOption::VALUE_NONE,
                    'Exclude unknown urls',
                    null
                ),
            ]
        );
    }

    /**
     * Execute the command like: ./console backup:db"
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteName = $input->getOption('name');
        $url = $input->getOption('urls');
        $trimmed_urls = trim($url);
        $urls = explode(',', $trimmed_urls);
        $ecommerce = $input->getOption('ecommerce') ? true : false;
        $siteSearch = $input->getOption('no-site-search') ? false : true;
        $searchKeywordParameters = $input->getOption('search-keyword-parameters');
        $searchCategoryParameters = $input->getOption('search-category-parameters');
        $excludedIps = $input->getOption('exclude-ips');
        $excludedQueryParameters = $input->getOption('exclude-query-parameters') ? true : false;
        $timezone = $input->getOption('timezone');
        $currency = $input->getOption('currency');
        $group = $input->getOption('group');
        $startDate = $input->getOption('start-date');
        $excludedUserAgents = $input->getOption('exclude-user-agents');
        $keepURLFragments = $input->getOption('keep-url-fragments') ? true : false;
        $type = $input->getOption('type');
        $settingValues = $input->getOption('settings-value'); // this need to be looked into - expects serialized json.
        $excludeUnknownUrls = $input->getOption('exclude-unknown-urls') ? true : false;

        $site = [
            'siteName' => $siteName,
            'urls' => $urls,
            'ecommerce' => $ecommerce,
            'siteSearch' => $siteSearch,
            'searchKeywordParameters' => $searchKeywordParameters,
            'searchCategoryParameters' => $searchCategoryParameters,
            'excludedIps' => $excludedIps,
            'excludedQueryParameters' => $excludedQueryParameters,
            'timezone' => $timezone,
            'currency' => $currency,
            'group' => $group,
            'startDate' => $startDate,
            'excludedUserAgents' => $excludedUserAgents,
            'keepURLFragments' => $keepURLFragments,
            'type' => $type,
            'settingValues' => $settingValues,
            'excludeUnknownUrls' => $excludeUnknownUrls,
        ];

        $output->writeln("Adding a new site");

        $new = new Site($site);
        $add_site = $new->add();
        $output->writeln("Site added");
    }
}
