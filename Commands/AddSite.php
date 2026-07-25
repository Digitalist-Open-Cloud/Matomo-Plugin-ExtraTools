<?php

/**
 * The Extra Tools plugin for Matomo.
 *
 * Copyright (C) Digitalist Open Cloud <cloud@digitalist.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Plugin\ConsoleCommand;
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

        foreach (
            [
            'name' => 'Name for the site',
            'urls' => 'URL for the site',
            'ecommerce' => 'If the site is a ecommerce site',
            'no-site-search' => 'If site search should be tracked',
            'search-keyword-parameters' => 'Search keyword parameters',
            'search-category-parameters' => 'Search category parameters',
            'exclude-ips' => 'Exclude IPs',
            'exclude-query-parameters' => 'Exclude query parameters',
            'timezone' => 'Timezone',
            'currency' => 'Currency',
            'group' => 'Group',
            'start-date' => 'Start date',
            'type' => 'Type',
            'settings-value' => 'Settings value',
            ] as $name => $description
        ) {
            $this->addOptionalValueOption($name, null, $description, null);
        }

        foreach (
            [
            'exclude-user-agents' => 'Exclude user agents',
            'keep-url-fragments' => 'Keep url fragments',
            'exclude-unknown-urls' => 'Exclude unknown urls',
            ] as $name => $description
        ) {
            $this->addNoValueOption($name, null, $description, null);
        }
    }

    /**
     * Execute the command like: ./console site:add
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $siteName = $input->getOption('name');
        if (empty($siteName)) {
            $output->writeln("<error>The website name can't be empty</error>");
            return self::SUCCESS;
        }
        $urls = $input->getOption('urls');
        if (isset($urls)) {
            $urls = trim($urls);
            $urls = explode(',', $urls);
        }
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

        $output->writeln("<info>Adding a new site</info>");
        $new = new Site($site);

        if ($new->exists()) {
            $output->writeln("<comment>Site $siteName already exists</comment>");

            return self::SUCCESS;
        }

        $add_site = $new->add();
        $output->writeln("<comment>Site $siteName added</comment>");

        return self::SUCCESS;
    }
}
