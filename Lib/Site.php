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

namespace Piwik\Plugins\ExtraTools\Lib;

use Piwik\Access;
use Piwik\Plugins\SitesManager\API as APISitesManager;

class Site
{
    protected $site;

    public function __construct($site)
    {
        $this->site = $site;
    }

    public function add()
    {
        $site = $this->site;

        return Access::doAsSuperUser(
            function () use ($site) {
                return APISitesManager::getInstance()->addSite(
                    $site['siteName'] ?? false,
                    $site['urls'] ?? null,
                    $site['ecommerce'] ?? null,
                    $site['siteSearch'] ?? null,
                    $site['searchKeywordParameters'] ?? null,
                    $site['searchCategoryParameters'] ?? null,
                    $site['excludedIps'] ?? null,
                    $site['excludedQueryParameters'] ?? null,
                    $site['timezone'] ?? null,
                    $site['currency'] ?? null,
                    $site['group'] ?? null,
                    $site['startDate'] ?? null,
                    $site['excludedUserAgents'] ?? null,
                    $site['keepURLFragments'] ?? null,
                    $site['type'] ?? null,
                    $site['settingValues'] ?? null,
                    $site['excludeUnknownUrls'] ?? null
                );
            }
        );
    }

    public function exists(): bool
    {
        $site = $this->site;

        $sites = Access::doAsSuperUser(
            function () use ($site): array {
                return APISitesManager::getInstance()->getPatternMatchSites($site['siteName'] ?? false, 1);
            }
        );

        return !empty($sites);
    }

    public function list()
    {
        $site_name = [];
        $list = APISitesManager::getInstance()->getAllSitesId();
        foreach ($list as $id) {
            $site_name[] = APISitesManager::getInstance()->getSiteFromId($id);
        }
        return $site_name;
    }

    public function record()
    {
        try {
            $result = APISitesManager::getInstance()->getSiteFromId($this->site);
            return $result['name'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete()
    {
        try {
            $delete = APISitesManager::getInstance()->deleteSite($this->site);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function addURL($id, $urls)
    {
        try {
            $add_url = APISitesManager::getInstance()->addSiteAliasUrls($id, $urls);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function totalSites()
    {
        $all = APISitesManager::getInstance()->getAllSites();
        return count($all);
    }
}
