<?php

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


        $result = Access::doAsSuperUser(
            function () use ($site) {
                $siteName = false;
                $urls  = null;
                $ecommerce  = null;
                $siteSearch  = null;
                $searchKeywordParameters  = null;
                $searchCategoryParameters  = null;
                $excludedIps  = null;
                $excludedQueryParameters  = null;
                $timezone  = null;
                $currency  = null;
                $group  = null;
                $startDate  = null;
                $excludedUserAgents  = null;
                $keepURLFragments  = null;
                $type  = null;
                $settingValues  = null;
                $excludeUnknownUrls  = null;
                $site = $this->site;
                $extract = extract($site);
                return APISitesManager::getInstance()->addSite(
                    $siteName,
                    $urls,
                    $ecommerce,
                    $siteSearch,
                    $searchKeywordParameters,
                    $searchCategoryParameters,
                    $excludedIps,
                    $excludedQueryParameters,
                    $timezone,
                    $currency,
                    $group,
                    $startDate,
                    $excludedUserAgents,
                    $keepURLFragments,
                    $type,
                    $settingValues,
                    $excludeUnknownUrls
                );
            }
        );
        return $result;
    }
    public function list()
    {
        $list = APISitesManager::getInstance()->getAllSitesId();

        foreach ($list as $id) {
            $site_name[] = APISitesManager::getInstance()->getSiteFromId($id);
        }

        return $site_name;
    }
}
