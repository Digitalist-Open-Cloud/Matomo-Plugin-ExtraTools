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


  class Install
{

    protected $config;
    protected $fileconfig;
    protected $options;
    protected $timestamp;

    /**
       * @var OutputInterface
       */
    private $output;


    public function __construct($options, OutputInterface $output, $fileconfig = null)
    {
        $this->config = Config::getInstance();
        $this->options = $options;
        $this->output = $output;
        $this->fileconfig = $fileconfig;
        $this->timestamp = false;
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
            if (isset($this->fileconfig->Config)) {
                $fileconfig = $this->fileconfig->Config;
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
        $this->checkEnvironment();
        $this->createSuperUser("$first_user", "$first_user_pass", "$first_user_email");
        $this->addWebsite("$first_site_name", "$first_site_url");
        $this->finish();
        $this->login($first_user, $first_user_pass);
       # $this->setupPlugins();
    }

      /**
       * @throws \DI\NotFoundException
       */
    protected function checkEnvironment() {
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
    protected function  initDBConnection()
    {
        $this->log('Initialising Database Connections');
        $config = $this->config;

        $config->General['session_save_handler'] = 'dbtable';
        $config->General['salt'] = Common::generateUniqId();
        $config->General['installation_in_progress'] = 1;
        // Connect to the database with retry timeout so any provisioning scripts & DB setup scripts are given a chance
        $retries = [10, 20, 30, 40, 50, 60, 70, 80];
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
    protected function createSuperUser($user = 'admin', $pass = 'admin', $email = 'admin@example.com')
    {
        $config_arr = [
            'login' => $user,
            'password' => $pass,
            'email' => $email,
        ];

        $this->log('Creating Super user');
        Access::doAsSuperUser(
            function () use ($config_arr) {
                $api = APIUsersManager::getInstance();
                if (!$api->userExists($config_arr['login'])
                    and !$api->userEmailExists($config_arr['email'])
                ) {
                    $api->addUser(
                        $config_arr['login'],
                        $config_arr['password'],
                        $config_arr['email']
                    );
                    $api->setSuperUserAccess($config_arr['login'], true);
                }
            }
        );
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
    protected function addWebsite($name = 'Foo site', $url = 'http://foo.bar')
    {
        $config_arr = [
          'site_name' => $name,
          'site_url' => $url,
          'base_domain' => $_SERVER['HTTP_HOST'],
        ];

        $this->log('Adding Primary Website');
        $result = Access::doAsSuperUser(
            function () use ($config_arr) {
                return APISitesManager::getInstance()->addSite(
                    $config_arr['site_name'],
                    $config_arr['site_url'],
                    0
                );
            }
        );
        $trustedHosts = [
            $config_arr['base_domain'],
        ];
        if (($host = $this->extractHost(urldecode($config_arr['site_url'])))
            !== false
        ) {
            $trustedHosts[] = $host;
        }
        $general = Config::getInstance()->General;
        $general['trusted_hosts'] = $trustedHosts;
        Config::getInstance()->General = $general;
        Config::getInstance()->forceSave();
    }


    /**
     * Finishes the installation. Removes 'installation_in_progress' in
     * the config file, do some uninstall/install on problematic plugins and updates core.
     */
    protected function finish()
    {
        // For some reason - these plugins are problematic in automatic install, therefor they are always installed in
        // this way.
        // @todo: Solve this in a non hacky way.

        $config = $this->config;

        $installed_plugins = $config->PluginsInstalled;

        foreach ($installed_plugins['PluginsInstalled'] as $installed_plugin) {
            $this->log("Checking $installed_plugin");

            if ($installed_plugin == 'LanguagesManager') {
                $languages_manager = new LanguagesManagerInstall();
                $languages_manager::install();
            }
            if ($installed_plugin == 'SegmentEditor') {
                $segment = new SegmentInstall();
                $segment::install();
            }
            if ($installed_plugin == 'Dashboard') {
                $dashboard = new DashboardInstall();
                $dashboard::install();
            }
            if ($installed_plugin == 'ScheduledReports') {
                $scheduledreports = new ScheduledReportsInstall();
                $scheduledreports::install();
            }
            if ($installed_plugin == 'PrivacyManager') {
                $privacy_manager = new PrivacyManagerInstall();
                $privacy_manager->install();
            }
            // Special, special snowflake....
            if ($installed_plugin == 'CustomVariables') {
                Manager::getInstance()->deactivatePlugin('CustomVariables');
                Manager::getInstance()->unloadPlugin('CustomVariables');
                CliManager::getInstance()->uninstallPlugin('CustomVariables');
                Manager::getInstance()->activatePlugin('CustomVariables');
            }
        }
        unset(
            $installed_plugin,
            $installed_plugins
        );

        $this->log('Finalising primary configurationprocedure');
        Manager::getInstance()->loadPluginTranslations();
        Manager::getInstance()->installLoadedPlugins();
        Manager::getInstance()->loadActivatedPlugins();
        Manager::getInstance()->installLoadedPlugins();

        unset(
            $config->General['installation_in_progress'],
            $config->database['adapter']
        );

        $config->forceSave();
        // Put in Activated plugins
        CliManager::getInstance()->loadActivatedPlugins();

        // @todo: Do with Updater class instead.
        exec(
            "php " . PIWIK_DOCUMENT_ROOT . "/console core:update --yes"
        );
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
    
    
    private function login($user, $pass) {
        $this->log("Now you can login with user <comment>$user</comment> and password <comment>$pass</comment>");
    }
}
