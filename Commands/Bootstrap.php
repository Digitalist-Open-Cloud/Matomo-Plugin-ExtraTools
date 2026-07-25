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

use Piwik\Access;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Tracker\Cache;
use Piwik\Plugins\SitesManager\API as SitesManagerApi;

/**
 * Bootstrap Matomo and warm all caches.
 *
 * Loading the console already boots the environment (config, DI container,
 * plugins). On top of that this command eagerly (re)builds the tracker cache
 * (general + per-site attributes) so the first real tracking/UI request does
 * not have to build it.
 */
class Bootstrap extends ConsoleCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command bootstraps Matomo and warms all caches.

It loads the environment (config, DI container and plugins) and rebuilds the
tracker cache: the general cache plus per-site attributes for every site.

<comment>Samples:</comment>
Warm everything:
<info>%command.name%</info>
Warm only specific sites:
<info>%command.name% --idsite=1,2,5</info>
Skip the per-site tracker cache (general cache only):
<info>%command.name% --skip-sites</info>';
        $this->setHelp($HelpText);
        $this->setName('extra:bootstrap');
        $this->setDescription('Bootstrap Matomo and warm all caches.');
        $this->addOptionalValueOption(
            'idsite',
            null,
            'Comma separated list of site IDs to warm. Defaults to all sites.',
            null
        );
        $this->addNoValueOption(
            'skip-sites',
            null,
            'Only warm the general tracker cache, skip per-site attributes.',
            null
        );
    }

    /**
     * Execute the command like: ./console extra:bootstrap
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $start = microtime(true);

        // Loading the console has already booted config, the DI container and
        // activated plugins. Make sure every installed plugin is loaded so their
        // Tracker.Cache.getSiteAttributes listeners contribute to the cache.
        PluginManager::getInstance()->loadActivatedPlugins();
        $output->writeln('<info>Environment bootstrapped (config, container, plugins loaded).</info>');

        // Warm the general (global) tracker cache.
        Cache::updateGeneralCache();
        $output->writeln('<info>General tracker cache warmed.</info>');

        if ($input->getOption('skip-sites')) {
            $output->writeln('<info>Skipping per-site tracker cache (--skip-sites).</info>');
            $this->done($start);
            return self::SUCCESS;
        }

        $idSites = $this->resolveSiteIds($input->getOption('idsite'));

        if (empty($idSites)) {
            $output->writeln('<comment>No sites found to warm.</comment>');
            $this->done($start);
            return self::SUCCESS;
        }

        $output->writeln(sprintf('<info>Warming tracker cache for %d site(s)...</info>', count($idSites)));

        // Rebuild all site caches in one delegated pass to avoid redundant clears.
        Cache::withDelegatedCacheClears(function () use ($idSites) {
            Cache::regenerateCacheWebsiteAttributes($idSites);
        });

        $output->writeln('<info>Per-site tracker cache warmed for sites: ' . implode(', ', $idSites) . '</info>');

        $this->done($start);
        return self::SUCCESS;
    }

    /**
     * Resolve the list of site IDs to warm.
     *
     * @param string|null $option Comma separated idsite option value.
     * @return int[]
     */
    private function resolveSiteIds($option): array
    {
        if (!empty($option)) {
            return array_values(array_unique(array_filter(array_map(
                'intval',
                explode(',', (string) $option)
            ), function ($id) {
                return $id > 0;
            })));
        }

        return Access::doAsSuperUser(function () {
            return SitesManagerApi::getInstance()->getAllSitesId();
        });
    }

    private function done(float $start): void
    {
        $seconds = round(microtime(true) - $start, 2);
        $this->writeSuccessMessage("Matomo bootstrapped and caches warmed in {$seconds}s.");
    }
}
