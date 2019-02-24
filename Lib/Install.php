<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Piwik\Filesystem;
use Piwik\DbHelper;
use Piwik\Db;
use Piwik\Db\Schema;
use Piwik\Plugins\LanguagesManager\Model as LanguagesManagerInstall;
use Piwik\Plugins\SegmentEditor\Model as SegmentInstall;
use Piwik\Plugins\Dashboard\Model as DashboardInstall;
use Piwik\Plugins\ScheduledReports\Model as ScheduledReportsInstall;
use Piwik\Plugins\PrivacyManager\PrivacyManager as PrivacyManagerInstall;
use Piwik\Config;
use Piwik\Common;
use Piwik\Access;
use Piwik\Updater;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugins\ExtraTools\Lib\CliManager;
use Piwik\Plugin\Manager;
use Piwik\Plugins\SitesManager\API as APISitesManager;
use Piwik\Version;
use Piwik\Option;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\ExtraTools\Lib\Requirements;
use Piwik\Plugins\ExtraTools\Lib\ConfigManipulation;

class Install
{
    protected $config;
    protected $fileconfig;
    protected $options;
    protected $timestamp;
    protected $user;

   /**
    * @var OutputInterface
    */
    private $output;


    public function __construct($options, OutputInterface $output, $fileconfig = null, $user = null)
    {
        $this->config = Config::getInstance();
        $this->options = $options;
        $this->output = $output;
        $this->fileconfig = $fileconfig;
        $this->timestamp = false;
        $this->user = $user;
    }

    public function execute()
    {
        $options = $this->options;
        if (isset($options['timestamp'])) {
            if ($options['timestamp'] == true) {
                $this->timestamp = true;
            }
        }

        $file_config = false;

        if (isset($this->fileconfig)) {
            $config_from_file = $this->fileconfig;
            if (isset($config_from_file->Config)) {
                $fileconfig = $config_from_file->Config;
            }
        }

        $first_user = $options['first-user'];

        if (isset($fileconfig['first-user'])) {
            $first_user = $fileconfig['first-user'];
        }

        $first_user_pass = $options['first-user-password'];
        if (isset($fileconfig['first-user-password'])) {
            $first_user_pass = $fileconfig['first-user-password'];
        }

        $first_user_email = $options['first-user-email'];
        if (isset($fileconfig['first-user-email'])) {
            $first_user_pass = $fileconfig['first-user-email'];
        }

        $first_site_name = $options['first-site-name'];
        if (isset($fileconfig['first-site-name'])) {
            $first_site_name = $fileconfig['first-site-name'];
        }

        $first_site_url = $options['first-site-url'];
        if (isset($fileconfig['first-site-url'])) {
            $first_site_name = $fileconfig['first-site-url'];
        }

        $this->output->writeln("<info>Starting <comment>install</comment></info>");

        $this->deleteCache();
        $this->initDBConnection();

        $this->tableCreation();
        $this->saveLanguage('en');
        # Environment check can not be used right now. It has a dependency on being installed.
      //  $this->checkEnvironment();
        $this->createSuperUser();
        $this->addWebsite();
        $this->installPlugins();
        $this->writeConfig();
        $this->finish();
        $this->login();
       # $this->setupPlugins();
    }

    /**
       * @throws \DI\NotFoundException
       */
    protected function checkEnvironment()
    {
        $this->log("Checking environment.");
        $check = new Requirements($this->output);
        if ($check->hasErrors()) {
            $this->log("<error>Errors found! They must be fixed before Matomo could be installed.</error>");
            exit;
        } else {
            $this->log("<comment>No environment errors found</comment>");
        }
    }


    /**
       * Initiate DB connection.
       */
    protected function initDBConnection()
    {

        $config_from_file = $this->fileconfig;
        if (isset($config_from_file->Config)) {
            $fileconfig = $config_from_file->Config;
        }

        $config = $this->config;
        $options = $this->options;

        if (isset($options)) {
            if (isset($options['db-username'])) {
                $config->database['username'] = $options['db-username'];
            }
            if (isset($options['password'])) {
                $config->database['password'] = $options['db-pass'];
            }
            if (isset($options['db-host'])) {
                $config->database['host'] = $options['db-host'];
            }
            if (isset($options['db-name'])) {
                $config->database['dbname'] = $options['db-name'];
            }
            if (isset($options['db-prefix'])) {
                $config->database['tables_prefix'] = $options['db-prefix'];
            }
        }


        if (isset($fileconfig)) {
            if (isset($fileconfig['database'])) {
                $database = $fileconfig['database'];
                $keys = [
                    'tables_prefix',
                    'host',
                    'username',
                    'password',
                    'dbname',
                ];
                foreach ($keys as $key) {
                    if (isset($database[$key])) {
                        $config->database[$key] = $database[$key];
                    }
                }
            }
        }

        $this->log('Initialising Database Connections');

        if (isset($fileconfig['General'])) {
            $general = $fileconfig['General'];
            if (isset($general['session_save_handler'])) {
                $config->General['session_save_handler'] = $general['session_save_handler'];
            } else {
                // defaults to database table.
                $config->General['session_save_handler'] = 'dbtable';
            }
            if (isset($general['salt'])) {
                $config->General['salt'] = $general['salt'];
            } else {
                $config->General['salt'] = Common::generateUniqId();
            }
        }
        // Tell Matomo that we are installing.
        $config->General['installation_in_progress'] = 1;
        // Connect to the database with retry timeout so any provisioning scripts & DB setup scripts are given a chance
        $retries = [10, 20, 30];
        foreach ($retries as $retry_timeout_index => $retry_timeout) {
            try {
                DbHelper::isDatabaseConnectionUTF8();
                break;
            } catch (\Exception $e) {
                $this->log(
                    "Database connection failed. Retrying in $retry_timeout seconds."
                );
                $this->log($e->getMessage());
                sleep($retry_timeout);
            }
        }
        if (!DbHelper::isDatabaseConnectionUTF8()) {  // Exception will be thrown if cannot connect
            $config->database['charset'] = 'utf8';
        }
        // Save the config.
        $config->forceSave();
        Db::createDatabaseObject($config->database);
    }

    /**
       * Write an output log.
       * @param $text string
       */
    protected function log($text)
    {
        $datestamp = '';
        if ($this->timestamp == true) {
            $datestamp = '[' .date("Y-m-d H:i:s") . '] ';
        }
        $this->output->writeln("<info>$datestamp$text</info>");
    }
    

    /**
     * Creates core database tables.
     */
    protected function tableCreation()
    {
        $this->log('Create Matomo core tables');
        $tablesInstalled = DbHelper::getTablesInstalled();
        if (count($tablesInstalled) === 0) {
            DbHelper::createTables();
            DbHelper::createAnonymousUser();
            DbHelper::recordInstallVersion();
            $this->updateComponents();
            Updater::recordComponentSuccessfullyUpdated('core', Version::VERSION);
        }
    }

    /**
     * Creates the first superuser.
     */
    protected function createSuperUser()
    {
        $fileconfig = $this->fileconfig;
        $config_from_file = $this->fileconfig;
        if (isset($config_from_file->Config)) {
            $fileconfig = $config_from_file->Config;
        }
        $options = $this->options;

        // Default values if none are set.
        $user = [
            'username' => 'admin',
            'pass' => 'password',
            'email' => 'admin@example.com',
        ];
        // Options (aka flags for install:matomo or env. variables overrides default)
        if (isset($options)) {
            if (isset($options['first-user'])) {
                $user['username'] = $options['first-user'];
            }
            if (isset($options['first-user-email'])) {
                $user['email'] = $options['first-user-email'];
            }
            if (isset($options['first-user-password'])) {
                $user['pass'] = $options['first-user-password'];
            }
        }
        // Settings from install file overrides everything.
        if (isset($fileconfig)) {
            if (isset($fileconfig['User'])) {
                $userdata = $fileconfig['User'];
                $keys = [
                    'username',
                    'pass',
                    'email',
                ];
                foreach ($keys as $key) {
                    if (isset($userdata[$key])) {
                        $user[$key] = $userdata[$key];
                    }
                }
            }
        }

        $this->log('Creating Super user');
        Access::doAsSuperUser(
            function () use ($user) {
                // split up the array - now we get $username, $pass and $email.
                extract($user);
                $api = APIUsersManager::getInstance();
                if (!$api->userExists($username)
                    and !$api->userEmailExists($email)
                ) {
                    $api->addUser(
                        $username,
                        $pass,
                        $email
                    );
                    $api->setSuperUserAccess($username, true);
                }
            }
        );
        $this->user = $user;
    }

    /**
       * Update components if needed.
       *
       * @return mixed
       * @throws \Exception
       */
    protected function updateComponents()
    {
        $this->log('Updating Components');
        Access::getInstance();
        return Access::doAsSuperUser(function () {
            $updater = new Updater();
            $componentsWithUpdateFile = $updater->getComponentUpdates();

            if (empty($componentsWithUpdateFile)) {
                return false;
            }
            $result = $updater->updateComponents($componentsWithUpdateFile);
            return $result;
        });
    }

    /**
     * Delete all cache data
     */
    private function deleteCache()
    {
        $this->output->writeln("<info>Deleting <comment>cache</comment></info>");
        Filesystem::deleteAllCacheOnUpdate();
    }

    /**
     * Sets up the initial website.
     */
    protected function addWebsite()
    {
        // defaults if nothing is overridden
        $site = [
            'name' => "Example",
            'url' => "http://example.com",
        ];

        $options = $this->options;
        $file_config = false;

        if (isset($options ["first-site-name"])) {
            $site['name'] = $options ["first-site-name"];
        }
        if (isset($options ["first-site-url"])) {
            $site['url'] = $options ["first-site-url"];
        }

        if (isset($this->fileconfig)) {
            $config_from_file = $this->fileconfig;
            if (isset($config_from_file->Config)) {
                $fileconfig = $config_from_file->Config;
            }
        }
        if (isset($fileconfig['Site'])) {
            $site_from_file = $fileconfig['Site'];
            if (isset($site_from_file['name'])) {
                $site['name'] = $site_from_file['name'];
            }
            if (isset($site_from_file['url'])) {
                $site['url'] = $site_from_file['url'];
            }
        }

        $this->log('Adding Primary Website');
        $result = Access::doAsSuperUser(
            function () use ($site) {
                return APISitesManager::getInstance()->addSite(
                    $site['name'],
                    $site['url'],
                    0
                );
            }
        );
        $name = $site['name'];
        $this->log("Added site  $name ");

        $trustedHosts = [];
        if (isset($_SERVER['SERVER_NAME'])) {
            $trustedHosts = [
                $_SERVER['SERVER_NAME'],
            ];
        }


        if (($host = $this->extractHost(urldecode($site['url'])))
            !== false
        ) {
            $trustedHosts[] = $host;
        }
        $general = Config::getInstance()->General;
        $general['trusted_hosts'] = $trustedHosts;
        Config::getInstance()->General = $general;
        Config::getInstance()->forceSave();
    }


    protected function installPlugins()
    {

        $config = $this->config;
        $installed_plugins = $config->PluginsInstalled;
        $file_config = false;
        $options = $this->options;
        $option_plugins = $options['plugins'];

        if (isset($this->fileconfig)) {
            $config_from_file = $this->fileconfig;
            if (isset($config_from_file->Config)) {
                $fileconfig = $config_from_file->Config;
            }
            if (isset($fileconfig['PluginsInstalled'])) {
                $installplugins = $fileconfig['PluginsInstalled'];
            } elseif (isset(($this->config->PluginsInstalled))) {
                foreach ($this->config->PluginsInstalled as $plugin) {
                    if (is_string(($plugin))) {
                        $installplugins[] = $plugin;
                    }
                }
            } elseif (isset($option_plugins)) {
                $plugins_to_activate[] = explode(',', $option_plugins);
            }
            if (isset($installplugins)) {
                foreach ($installplugins as $plugin) {
                    Manager::getInstance()->activatePlugin($plugin);
                    $this->log("Activated $plugin");
                }
                Manager::getInstance()->loadPluginTranslations();
                Manager::getInstance()->loadActivatedPlugins();
                Manager::getInstance()->installLoadedPlugins();
                $config->PluginsInstalled = $installplugins;
            }
        }
    }


    /**
     *
     */
    protected function writeConfig() {
        $file_config = false;
        if (isset($this->fileconfig)) {
            $config_from_file = $this->fileconfig;
            if (isset($config_from_file->Config)) {
                $fileconfig = $config_from_file->Config;
            }
            $general_config =  $fileconfig['General'];

            if (isset($general_config )) {
                foreach ($general_config  as $key => $value) {

                    $config_write = new ConfigManipulation($this->config, $this->output);
                    $config_write->saveConfig('General', "$key", "$value");
                }

            }
        }
    }

    /**
     * Finishes the installation. Removes 'installation_in_progress' in
     * the config file, do some uninstall/install on problematic plugins and updates core.
     */
    protected function finish()
    {
        $config = $this->config;
        $this->log('Finalising...');

        unset(
            $config->General['installation_in_progress'],
            $config->database['adapter']
        );

        $config->forceSave();
        $this->log("<comment>We are done! Welcome to Matomo!</comment>");
    }

    /**
     * Extract host from URL
     *
     * @param string $url URL
     *
     * @return string|false
     */
    protected function extractHost($url)
    {
        $urlParts = parse_url($url);
        if (isset($urlParts['host']) && strlen($host = $urlParts['host'])) {
            return $host;
        }
        return false;
    }

    /**
     * Records the Matomo version a user used when installing this Matomo for the first time
     */
    public static function recordInstallVersion()
    {
        Schema::getInstance()->recordInstallVersion();
    }

    /**
     * Sets the Geolocation
     * [geo_provider] is mandatory. Only correct value implemented is
     * [geoip_pecl]
     * TODO: Need to make a better solution than this so we can be independent
     */
    protected function setGeo()
    {
        $this->log('Setting Geolocation');
        Option::set(
            'usercountry.location_provider',
            $this->config['geo_provider']
        );
        if ($this->config['geo_provider'] === 'geoip_pecl') {
            Option::set('geoip.isp_db_url', '');
            Option::set(
                'geoip.loc_db_url',
                'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz'
            );
            Option::set('geoip.org_db_url', '');
            Option::set('geoip.updater_period', 'month');
        }
    }

    /**
     * Setup plugins
     * [plugins] in config should be text based and already extracted in the
     * plugins piwik directory
     */
    protected function setupPlugins()
    {
        $this->log('Setting up Extra Plugins');
        echo exec("php " . PIWIK_DOCUMENT_ROOT . "/console core:clear-caches")
            . "\n";

            $config = Config::getInstance();
        foreach ($this->config->getFromLocalConfig('Plugins') as $pi_arr) {
            foreach ($pi_arr as $pi) {
                $config->Plugins[] = $pi;
            }
        }
            // Now go and activate them
        foreach ($config->Plugins as $plugin_txt) {
            if (is_string($plugin_txt)) {
                echo exec(
                    "php " . PIWIK_DOCUMENT_ROOT . "/console plugin:activate "
                        . $plugin_txt
                ) . "\n";
                $config->PluginsInstalled[] = $plugin_txt;
                $config->Plugins[] = $plugin_txt;
            }
        }
            $config->forceSave();
            // And Update Core
            // TODO: Update core exists on several places, this should be consolidated
            exec("php " . PIWIK_DOCUMENT_ROOT . "/console core:update --yes");
    }

    /**
     * Save language selection in session-store
     */
    public function saveLanguage($lang)
    {
        $language = $lang;
        LanguagesManager::setLanguageForSession($language);
    }


    private function login()
    {
        extract($this->user);
        $this->log("Now you can login with user <comment>$username</comment> and password <comment>$pass</comment>");
    }
}
