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

use Piwik\Application\Kernel\PluginList;
use Piwik\Cache;
use Piwik\Columns\Dimension;
use Piwik\Config as PiwikConfig;
use Piwik\Container\StaticContainer;
use Piwik\Development;
use Piwik\EventDispatcher;
use Piwik\Filesystem;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugin\Dimension\ActionDimension;
use Piwik\Plugin\Dimension\ConversionDimension;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Settings\Storage as SettingsStorage;
use Piwik\SettingsPiwik;
use Piwik\Translation\Translator;
use Piwik\Updater;
use Piwik\Plugin\MetadataLoader;

/**
 * The singleton that manages plugin loading/unloading and installation/uninstallation.
 */
class CliManager
{
    /**
     * @return self
     */
    public static function getInstance()
    {
        return StaticContainer::get('Piwik\Plugins\ExtraTools\Lib\CliManager');
    }

    protected $pluginsToLoad = array();

    /**
     * @var Plugin[]
     */
    protected $loadedPlugins = array();
    /**
     * Default theme used in Piwik.
     */
    public const DEFAULT_THEME = "Morpheus";

    protected $doLoadAlwaysActivatedPlugins = true;

    // These are always activated and cannot be deactivated
    protected $pluginToAlwaysActivate = array(
        'CoreHome',
        'Diagnostics',
        'CoreUpdater',
        'CoreAdminHome',
        'CoreConsole',
        'CorePluginsAdmin',
        'CoreVisualizations',
        'Installation',
        'SitesManager',
        'UsersManager',
        'Intl',
        'API',
        'Proxy',
        'LanguagesManager',
        'WebsiteMeasurable',

        // default Piwik theme, always enabled
        self::DEFAULT_THEME,
    );

    /**
     * @var PluginList
     */
    private $pluginList;

    public function __construct(PluginList $pluginList)
    {
        $this->pluginList = $pluginList;
    }

    /**
     * Loads plugin that are enabled
     */
    public function loadActivatedPlugins()
    {
        $pluginsToLoad = $this->getActivatedPluginsFromConfig();
        if (!SettingsPiwik::isInternetEnabled()) {
            $pluginsToLoad = array_filter($pluginsToLoad, function ($name) {
                $plugin = CliManager::makePluginClass($name);
                return !$plugin->requiresInternetConnection();
            });
        }
        $this->loadPlugins($pluginsToLoad);
    }

    /**
     * Update Plugins config
     *
     * @param array $pluginsToLoad Plugins
     */
    private function updatePluginsConfig($pluginsToLoad)
    {
        $pluginsToLoad = $this->pluginList->sortPluginsAndRespectDependencies($pluginsToLoad);
        $section = PiwikConfig::getInstance()->Plugins;
        $section['Plugins'] = $pluginsToLoad;
        PiwikConfig::getInstance()->Plugins = $section;
    }

    /**
     * Returns true if plugin is always activated
     *
     * @param string $name Name of plugin
     * @return bool
     */
    private function isPluginAlwaysActivated($name)
    {
        return in_array($name, $this->pluginToAlwaysActivate);
    }

    /**
     * Returns true if the plugin can be uninstalled. Any non-core plugin can be uninstalled.
     *
     * @param $name
     * @return bool
     */
    private function isPluginUninstallable($name)
    {
        return !$this->isPluginBundledWithCore($name);
    }

    /**
     * Returns `true` if a plugin has been activated.
     *
     * @param string $name Name of plugin, eg, `'Actions'`.
     * @return bool
     * @api
     */
    public function isPluginActivated($name)
    {
        return in_array($name, $this->pluginsToLoad)
            || ($this->doLoadAlwaysActivatedPlugins && $this->isPluginAlwaysActivated($name));
    }

    /**
     * Returns `true` if plugin is loaded (in memory).
     *
     * @param string $name Name of plugin, eg, `'Acions'`.
     * @return bool
     * @api
     */
    public function isPluginLoaded($name)
    {
        return isset($this->loadedPlugins[$name]);
    }

    /**
     * Reads the directories inside the plugins/ directory and returns their names in an array
     *
     * @return array
     */
    public function readPluginsDirectory()
    {
        $result = array();
        foreach (self::getPluginsDirectories() as $pluginsDir) {
            $pluginsName = glob($pluginsDir . '*', GLOB_ONLYDIR);
            if ($pluginsName != false) {
                foreach ($pluginsName as $path) {
                    if (self::pluginStructureLooksValid($path)) {
                        $result[] = basename($path);
                    }
                }
            }
        }

        sort($result);

        return $result;
    }

    public static function getPluginsDirectory()
    {
        return PIWIK_INCLUDE_PATH . '/plugins/';
    }

    /**
     * Deactivate plugin
     *
     * @param string $pluginName Name of plugin
     */
    public function deactivatePlugin($pluginName)
    {
        $this->clearCache($pluginName);

        // execute deactivate() to let the plugin do cleanups
        $this->executePluginDeactivate($pluginName);

        $this->unloadPluginFromMemory($pluginName);

        $this->removePluginFromConfig($pluginName);

        /**
         * Event triggered after a plugin has been deactivated.
         *
         * @param string $pluginName The plugin that has been deactivated.
         */
      //  Piwik::postEvent('PluginManager.pluginDeactivated', array($pluginName));
    }

    /**
     * Uninstalls a Plugin (deletes plugin files from the disk)
     * Only deactivated plugins can be uninstalled
     *
     * @param $pluginName
     * @param  $delete
     * @throws \Exception
     * @return bool
     */
    public function uninstallPlugin($pluginName, $delete = false)
    {
        if ($this->isPluginLoaded($pluginName)) {
            throw new \Exception(
                "To uninstall the plugin $pluginName, first disable it in Matomo > Settings > Plugins"
            );
        }
        $this->loadAllPluginsAndGetTheirInfo();

        SettingsStorage\Backend\PluginSettingsTable::removeAllSettingsForPlugin($pluginName);
        SettingsStorage\Backend\MeasurableSettingsTable::removeAllSettingsForPlugin($pluginName);


                $this->executePluginDeactivate($pluginName);
                $this->executePluginUninstall($pluginName);
                $this->unloadPluginFromMemory($pluginName);
                $this->removePluginFromConfig($pluginName);
                $this->removeInstalledVersionFromOptionTable($pluginName);
                $this->clearCache($pluginName);
        /**
         * Event triggered after a plugin has been uninstalled.
         *
         * @param string $pluginName The plugin that has been uninstalled.
         */
        //Piwik::postEvent('PluginManager.pluginUninstalled', array($pluginName));

        return true;
    }

    /**
     * @param string $pluginName
     */
    private function clearCache($pluginName)
    {
        Filesystem::deleteAllCacheOnUpdate($pluginName);
    }

    /**
     * Returns info regarding all plugins. Loads plugins that can be loaded.
     *
     * @return array An array that maps plugin names with arrays of plugin information. Plugin
     *               information consists of the following entries:
     *
     *               - **activated**: Whether the plugin is activated.
     *               - **alwaysActivated**: Whether the plugin should always be activated,
     *                                      or not.
     *               - **uninstallable**: Whether the plugin is uninstallable or not.
     *               - **invalid**: If the plugin is invalid, this property will be set to true.
     *                              If the plugin is not invalid, this property will not exist.
     *               - **info**: If the plugin was loaded, will hold the plugin information.
     *                           See {@link Piwik\Plugin::getInformation()}.
     * @api
     */
    public function loadAllPluginsAndGetTheirInfo()
    {
        /** @var Translator $translator */
        $translator = StaticContainer::get('Piwik\Translation\Translator');

        $plugins = array();

        $listPlugins = array_merge(
            $this->readPluginsDirectory(),
            $this->pluginList->getActivatedPlugins()
        );
        $listPlugins = array_unique($listPlugins);
        $internetFeaturesEnabled = SettingsPiwik::isInternetEnabled();
        foreach ($listPlugins as $pluginName) {
            // Hide plugins that are never going to be used
            if ($this->isPluginBogus($pluginName)) {
                continue;
            }

            // If the plugin is not core and looks bogus, do not load
            if ($this->isPluginThirdPartyAndBogus($pluginName)) {
                $info = array(
                    'invalid'         => true,
                    'activated'       => false,
                    'alwaysActivated' => false,
                    'uninstallable'   => true,
                );
            } else {
                $translator->addDirectory(self::getPluginsDirectory() . $pluginName . '/lang');
                $this->loadPlugin($pluginName);
                $info = array(
                    'activated'       => $this->isPluginActivated($pluginName),
                    'alwaysActivated' => $this->isPluginAlwaysActivated($pluginName),
                    'uninstallable'   => $this->isPluginUninstallable($pluginName),
                );
            }

            $plugins[$pluginName] = $info;
        }

        $loadedPlugins = $this->getLoadedPlugins();
        foreach ($loadedPlugins as $oPlugin) {
            $pluginName = $oPlugin->getPluginName();

            $info = array(
                'info' => $oPlugin->getInformation(),
                'activated'       => $this->isPluginActivated($pluginName),
                'alwaysActivated' => $this->isPluginAlwaysActivated($pluginName),
                'missingRequirements' => $oPlugin->getMissingDependenciesAsString(),
                'uninstallable' => $this->isPluginUninstallable($pluginName),
            );
            $plugins[$pluginName] = $info;
        }

        return $plugins;
    }

    protected static function isManifestFileFound($path)
    {
        return file_exists($path . "/" . MetadataLoader::PLUGIN_JSON_FILENAME);
    }

    /**
     * Returns `true` if the plugin is bundled with core or `false` if it is third party.
     *
     * @param string $name The name of the plugin, eg, `'Actions'`.
     * @return bool
     */
    public function isPluginBundledWithCore($name)
    {
        return $this->isPluginEnabledByDefault($name)
            || in_array($name, $this->pluginList->getCorePluginsDisabledByDefault())
            || $name == self::DEFAULT_THEME;
    }

    /**
     * @param $pluginName
     * @return bool
     * @ignore
     */
    public function isPluginThirdPartyAndBogus($pluginName)
    {
        if ($this->isPluginBundledWithCore($pluginName)) {
            return false;
        }
        if ($this->isPluginBogus($pluginName)) {
            return true;
        }

        $path = $this->getPluginsDirectory() . $pluginName;
        if (!$this->isManifestFileFound($path)) {
            return true;
        }
        return false;
    }

    /**
     * Load AND activates the specified plugins. It will also overwrite all previously loaded plugins, so it acts
     * as a setter.
     *
     * @param array $pluginsToLoad Array of plugins to load.
     */
    public function loadPlugins(array $pluginsToLoad)
    {
        $this->pluginsToLoad = $this->makePluginsToLoad($pluginsToLoad);
        $this->reloadActivatedPlugins();
    }

    /**
     * Returns an array mapping loaded plugin names with their plugin objects, eg,
     *
     *     array(
     *         'UserCountry' => Plugin $pluginObject,
     *         'UserLanguage' => Plugin $pluginObject,
     *     );
     *
     * @return Plugin[]
     */
    public function getLoadedPlugins()
    {
        return $this->loadedPlugins;
    }

    /**
     * Returns a list of all names of currently activated plugin eg,
     *
     *     array(
     *         'UserCountry'
     *         'UserLanguage'
     *     );
     *
     * @return string[]
     */
    public function getActivatedPlugins()
    {
        return $this->pluginsToLoad;
    }

    public function getActivatedPluginsFromConfig()
    {
        $plugins = $this->pluginList->getActivatedPlugins();

        return $this->makePluginsToLoad($plugins);
    }

    /**
     * Returns a Plugin object by name.
     *
     * @param string $name The name of the plugin, eg, `'Actions'`.
     * @throws \Exception If the plugin has not been loaded.
     * @return Plugin
     */
    public function getLoadedPlugin($name)
    {
        if (!isset($this->loadedPlugins[$name]) || is_null($this->loadedPlugins[$name])) {
            throw new \Exception("The plugin '$name' has not been loaded.");
        }
        return $this->loadedPlugins[$name];
    }

    /**
     * Load the plugins classes installed.
     * Register the observers for every plugin.
     */
    private function reloadActivatedPlugins()
    {
        $pluginsToPostPendingEventsTo = array();
        foreach ($this->pluginsToLoad as $pluginName) {
            $pluginsToPostPendingEventsTo = $this->reloadActivatedPlugin($pluginName, $pluginsToPostPendingEventsTo);
        }

        // post pending events after all plugins are successfully loaded
        foreach ($pluginsToPostPendingEventsTo as $plugin) {
            EventDispatcher::getInstance()->postPendingEventsTo($plugin);
        }
    }

    private function reloadActivatedPlugin($pluginName, $pluginsToPostPendingEventsTo)
    {
        if ($this->isPluginLoaded($pluginName) || $this->isPluginThirdPartyAndBogus($pluginName)) {
            return $pluginsToPostPendingEventsTo;
        }

        $newPlugin = $this->loadPlugin($pluginName);

        if ($newPlugin === null) {
            return $pluginsToPostPendingEventsTo;
        }

        $requirements = $newPlugin->getMissingDependencies();

        if (!empty($requirements)) {
            foreach ($requirements as $requirement) {
                $possiblePluginName = $requirement['requirement'];
                if (in_array($possiblePluginName, $this->pluginsToLoad, $strict = true)) {
                    $pluginsToPostPendingEventsTo = $this->reloadActivatedPlugin(
                        $possiblePluginName,
                        $pluginsToPostPendingEventsTo
                    );
                }
            }
        }

        if ($newPlugin->hasMissingDependencies()) {
            $this->unloadPluginFromMemory($pluginName);

            // at this state we do not know yet whether current user has super user access. We do not even know
            // if someone is actually logged in.
            $message  = Piwik::translate(
                'CorePluginsAdmin_WeCouldNotLoadThePluginAsItHasMissingDependencies',
                array(
                        $pluginName,
                        $newPlugin->getMissingDependenciesAsString()
                    )
            );
            $message .= ' ';
            $message .= Piwik::translate('General_PleaseContactYourPiwikAdministrator');

            $notification = new Notification($message);
            $notification->context = Notification::CONTEXT_ERROR;
            Notification\Manager::notify('PluginManager_PluginUnloaded', $notification);
            return $pluginsToPostPendingEventsTo;
        }

        if (
            $newPlugin->isPremiumFeature()
            && SettingsPiwik::isInternetEnabled()
            && !Development::isEnabled()
            && $this->isPluginActivated('Marketplace')
            && $this->isPluginActivated($pluginName)
        ) {
            $cacheKey = 'MarketplacePluginMissingLicense' . $pluginName;
            $cache = self::getLicenseCache();

            if ($cache->contains($cacheKey)) {
                $pluginLicenseInfo = $cache->fetch($cacheKey);
            } else {
                try {
                    $plugins = StaticContainer::get('Piwik\Plugins\Marketplace\Plugins');
                    $licenseInfo = $plugins->getLicenseValidInfo($pluginName);
                } catch (\Exception $e) {
                    $licenseInfo = array();
                }

                $pluginLicenseInfo = array('missing' => !empty($licenseInfo['isMissingLicense']));
                $sixHours = 3600 * 6;
                $cache->save($cacheKey, $pluginLicenseInfo, $sixHours);
            }

            if (!empty($pluginLicenseInfo['missing']) && (!defined('PIWIK_TEST_MODE') || !PIWIK_TEST_MODE)) {
                $this->unloadPluginFromMemory($pluginName);
                return $pluginsToPostPendingEventsTo;
            }
        }

        $pluginsToPostPendingEventsTo[] = $newPlugin;

        return $pluginsToPostPendingEventsTo;
    }

    public static function getLicenseCache()
    {
        return Cache::getLazyCache();
    }

    /**
     * Loads the plugin filename and instantiates the plugin with the given name, eg. UserCountry.
     * Contrary to loadPlugins() it does not activate the plugin, it only loads it.
     *
     * @param string $pluginName
     * @throws \Exception
     * @return Plugin|null
     */
    public function loadPlugin($pluginName)
    {
        if (isset($this->loadedPlugins[$pluginName])) {
            return $this->loadedPlugins[$pluginName];
        }
        $newPlugin = $this->makePluginClass($pluginName);

        $this->addLoadedPlugin($pluginName, $newPlugin);
        return $newPlugin;
    }

    public function isValidPluginName($pluginName)
    {
        return (bool) preg_match('/^[a-zA-Z]([a-zA-Z0-9_]*)$/D', $pluginName);
    }

    /**
     * @param $pluginName
     * @return Plugin
     * @throws \Exception
     */
    protected function makePluginClass($pluginName)
    {
        $pluginFileName = sprintf("%s/%s.php", $pluginName, $pluginName);
        $pluginClassName = $pluginName;

        if (!$this->isValidPluginName($pluginName)) {
            throw new \Exception(sprintf("The plugin filename '%s' is not a valid plugin name", $pluginFileName));
        }

        $path = self::getPluginsDirectory() . $pluginFileName;

        if (!file_exists($path)) {
            // Create the smallest minimal Piwik Plugin
            // Eg. Used for Morpheus default theme which does not have a Morpheus.php file
            return new Plugin($pluginName);
        }

        require_once $path;

        $namespacedClass = $this->getClassNamePlugin($pluginName);
        if (!class_exists($namespacedClass, false)) {
            throw new \Exception("The class $pluginClassName couldn't be found in the file '$path'");
        }
        $newPlugin = new $namespacedClass();

        if (!($newPlugin instanceof Plugin)) {
            throw new \Exception("The plugin $pluginClassName in the file $path must inherit from Plugin.");
        }
        return $newPlugin;
    }

    protected function getClassNamePlugin($pluginName)
    {
        $className = $pluginName;
        if ($pluginName == 'API') {
            $className = 'Plugin';
        }
        return "\\Piwik\\Plugins\\$pluginName\\$className";
    }

    /**
     * Unload plugin
     *
     * @param Plugin|string $plugin
     * @throws \Exception
     */
    public function unloadPlugin($plugin)
    {
        if (!($plugin instanceof Plugin)) {
            $oPlugin = $this->loadPlugin($plugin);
            if ($oPlugin === null) {
                unset($this->loadedPlugins[$plugin]);
                return;
            }

            $plugin = $oPlugin;
        }

        unset($this->loadedPlugins[$plugin->getPluginName()]);
    }

    /**
     * Add a plugin in the loaded plugins array
     *
     * @param string $pluginName plugin name without prefix (eg. 'UserCountry')
     * @param Plugin $newPlugin
     * @internal
     */
    public function addLoadedPlugin($pluginName, Plugin $newPlugin)
    {
        $this->loadedPlugins[$pluginName] = $newPlugin;
    }

    private static function pluginStructureLooksValid($path)
    {
        $name = basename($path);
        return file_exists($path . "/" . $name . ".php")
            || self::isManifestFileFound($path);
    }

    /**
     * @param $pluginName
     */
    private function removePluginFromPluginsConfig($pluginName)
    {
        $pluginsEnabled = $this->pluginList->getActivatedPlugins();
        $key = array_search($pluginName, $pluginsEnabled);
        if ($key !== false) {
            unset($pluginsEnabled[$key]);
        }
        $this->updatePluginsConfig($pluginsEnabled);
    }

    /**
     * @param $pluginName
     * @return bool
     */
    private function isPluginBogus($pluginName)
    {
        $bogusPlugins = array(
            'PluginMarketplace', //defines a plugin.json but 1.x Piwik plugin
            'DoNotTrack', // Removed in 2.0.3
            'AnonymizeIP', // Removed in 2.0.3
        );
        return in_array($pluginName, $bogusPlugins);
    }

    /**
     * @param $pluginName
     */
    private function executePluginDeactivate($pluginName)
    {
        if (!$this->isPluginBogus($pluginName)) {
            $plugin = $this->loadPlugin($pluginName);
            if ($plugin !== null) {
                $plugin->deactivate();
            }
        }
    }

    /**
     * @param $pluginName
     */
    private function unloadPluginFromMemory($pluginName)
    {
        $this->unloadPlugin($pluginName);

        $key = array_search($pluginName, $this->pluginsToLoad);
        if ($key !== false) {
            unset($this->pluginsToLoad[$key]);
        }
    }

    /**
     * @param $pluginName
     */
    private function removePluginFromConfig($pluginName)
    {
        $this->removePluginFromPluginsConfig($pluginName);
        PiwikConfig::getInstance()->forceSave();
    }

    /**
     * @param $pluginName
     */
    private function executePluginUninstall($pluginName)
    {
        try {
            $plugin = $this->getLoadedPlugin($pluginName);
            $plugin->uninstall();
        } catch (\Exception $e) {
        }

        if (empty($plugin)) {
            return;
        }

        try {
            $visitDimensions = VisitDimension::getAllDimensions();

            foreach (VisitDimension::getDimensions($plugin) as $dimension) {
                $this->uninstallDimension(VisitDimension::INSTALLER_PREFIX, $dimension, $visitDimensions);
            }
        } catch (\Exception $e) {
        }

        try {
            $actionDimensions = ActionDimension::getAllDimensions();

            foreach (ActionDimension::getDimensions($plugin) as $dimension) {
                $this->uninstallDimension(ActionDimension::INSTALLER_PREFIX, $dimension, $actionDimensions);
            }
        } catch (\Exception $e) {
        }

        try {
            $conversionDimensions = ConversionDimension::getAllDimensions();

            foreach (ConversionDimension::getDimensions($plugin) as $dimension) {
                $this->uninstallDimension(ConversionDimension::INSTALLER_PREFIX, $dimension, $conversionDimensions);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param VisitDimension|ActionDimension|ConversionDimension $dimension
     * @param VisitDimension[]|ActionDimension[]|ConversionDimension[] $allDimensions
     * @return bool
     */
    private function doesAnotherPluginDefineSameColumnWithDbEntry($dimension, $allDimensions)
    {
        $module = $dimension->getModule();
        $columnName = $dimension->getColumnName();

        foreach ($allDimensions as $dim) {
            if (
                $dim->getColumnName() === $columnName &&
                $dim->hasColumnType() &&
                $dim->getModule() !== $module
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $prefix column installer prefix
     * @param ConversionDimension|VisitDimension|ActionDimension $dimension
     * @param VisitDimension[]|ActionDimension[]|ConversionDimension[] $allDimensions
     */
    private function uninstallDimension($prefix, Dimension $dimension, $allDimensions)
    {
        if (!$this->doesAnotherPluginDefineSameColumnWithDbEntry($dimension, $allDimensions)) {
            $dimension->uninstall();

            $this->removeInstalledVersionFromOptionTable($prefix . $dimension->getColumnName());
        }
    }

    private function removeInstalledVersionFromOptionTable($name)
    {
        $updater = new Updater();
        $updater->markComponentSuccessfullyUninstalled($name);
    }

    /**
     * Reading the plugins from the global.ini.php config file
     *
     * @return array
     */
    protected function getPluginsFromGlobalIniConfigFile()
    {
        return $this->pluginList->getPluginsBundledWithPiwik();
    }

    /**
     * @param $name
     * @return bool
     */
    protected function isPluginEnabledByDefault($name)
    {
        $pluginsBundledWithPiwik = $this->getPluginsFromGlobalIniConfigFile();
        if (empty($pluginsBundledWithPiwik)) {
            return false;
        }
        return in_array($name, $pluginsBundledWithPiwik);
    }

    /**
     * @param array $pluginsToLoad
     * @return array
     */
    private function makePluginsToLoad(array $pluginsToLoad)
    {
        $pluginsToLoad = array_unique($pluginsToLoad);
        if ($this->doLoadAlwaysActivatedPlugins) {
            $pluginsToLoad = array_merge($pluginsToLoad, $this->pluginToAlwaysActivate);
        }
        $pluginsToLoad = array_unique($pluginsToLoad);
        $pluginsToLoad = $this->pluginList->sortPlugins($pluginsToLoad);
        return $pluginsToLoad;
    }

    /**
     * Returns the path to all plugins directories. Each plugins directory may contain several plugins.
     * All paths have a trailing slash '/'.
     * @return string[]
     * @api
     */
    public static function getPluginsDirectories()
    {
        $dirs = array(self::getPluginsDirectory());

        if (!empty($GLOBALS['MATOMO_PLUGIN_DIRS'])) {
            $extraDirs = array_map(function ($dir) {
                return rtrim($dir['pluginsPathAbsolute'], '/') . '/';
            }, $GLOBALS['MATOMO_PLUGIN_DIRS']);
            $dirs = array_merge($dirs, $extraDirs);
        }

        return $dirs;
    }
}
