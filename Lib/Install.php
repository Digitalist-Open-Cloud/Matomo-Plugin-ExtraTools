<?php

  namespace Piwik\Plugins\MatomoExtraTools\Lib;

  use Piwik\Filesystem;
  use Piwik\DbHelper;
  use Piwik\Db\Schema;
  use Piwik\Plugins\LanguagesManager\Model as LanguageManagerInstall;
  use Piwik\Plugins\SegmentEditor\Model as SegmentInstall;
  use Piwik\Plugins\Dashboard\Model as DashboardInstall;
  use Piwik\Plugins\ScheduledReports\Model as ScheduledReportsInstall;
  use Symfony\Component\Console\Output\OutputInterface;
  use Piwik\Config;
  use Piwik\Common;
  use Piwik\Access;
  use Piwik\Updater;
  use Piwik\Plugins\UsersManager\API as APIUsersManager;
  use Piwik\Plugin\Manager;
  use Piwik\Plugins\SitesManager\API as APISitesManager;
  use Piwik\Version;
  use Piwik\Option;
  use Piwik\Plugins\LanguagesManager\LanguagesManager;

class Install
{

    protected $config;

    /**
       * @var OutputInterface
       */
    private $output;


    public function __construct($config, OutputInterface $output)
    {
        $this->config = Config::getInstance();
        $this->output = $output;
    }

    public function execute()
    {
        $this->output->writeln("<info>Starting <comment>install</comment></info>");
        $this->deleteCache();
        $this->initDBConnection();
        $this->tableCreation();
        $this->saveLanguage('en');
        $this->createSuperUser('admin', 'admin1234', 'mikkeschiren@foo.com');
        $this->addWebsite('Foo', 'http://bar.foo');
        $this->finish();
        #$this->setupPlugins();
    }


    /**
     * Initialises and saves the database connection to Matomo
     * [database] should be in config. Should be an array with keys [host],
     * [adapter], [username], [password], [dbname] and [tables_prefix]
     * TODO: Perhaps try to create database schema if it doesn't exist
     */
    protected function initDBConnection()
    {
        $this->log('Initialising Database Connections');
        $config = Config::getInstance();



        $config->General['session_save_handler'] = 'dbtable';
        $config->General['salt'] = Common::generateUniqId();
        $config->General['installation_in_progress'] = 1;
        //$config->database = $this->config['database'];
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
    }




    protected function log($text)
    {
        echo "$text\n";
    }
    


    /**
     * Performs the initial table creation for Matomo, if needed
     */
    protected function tableCreation()
    {
        $this->log('Ensuring Tables are Created');
        $tablesInstalled = DbHelper::getTablesInstalled();
        if (count($tablesInstalled) === 0) {
            DbHelper::createTables();
            DbHelper::createAnonymousUser();
            DbHelper::recordInstallVersion();
            $this->updateComponents();

            Updater::recordComponentSuccessfullyUpdated('core', Version::VERSION);


            // $this->updateComponents();
        }
    }

    /**
     * Creates the default superuser, if needed
     * [login], [password] and [email] should all be set in the config
     */
    protected function createSuperUser($user, $pass, $email)
    {
        $config_arr = [
            'login' => $user,
            'password' => $pass,
            'email' => $email,
        ];

        $this->log('Ensuring Users get Created');
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
        $this->output->writeln("<info>  Deleting <comment>cache</comment></info>");
        Filesystem::deleteAllCacheOnUpdate();
    }
    private function isMatomoInstalled()
    {
    }


    /**
     * Sets up the initial website, if applicable, (site ID 1) to track
     * [site_name], [site_url] and [base_domain] should all be set in config
     */
    protected function addWebsite($name, $url)
    {

        $config_arr = [
          'site_name' => $name,
          'site_url' => $url,
          'base_domain' => '0.0.0.0',
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
     * the config file and updates core.
     */
    protected function finish()
    {

        $language_manager = new LanguageManagerInstall();
        $language_manager::install();
        $segment = new SegmentInstall();
        $segment::install();
        $dashboard = new DashboardInstall();
        $dashboard::install();
        $scheduledreports = new ScheduledReportsInstall();
        $scheduledreports::install();



        $this->log('Finalising primary configurationprocedure');
        Manager::getInstance()->loadPluginTranslations();


        Manager::getInstance()->loadPlugin('LanguagesManager');
        Manager::getInstance()->activatePlugin('LanguagesManager');
        //Manager::getInstance()->deactivatePlugin('LanguagesManager');
        Manager::getInstance()->installLoadedPlugins();
        Manager::getInstance()->loadActivatedPlugins();
        Manager::getInstance()->installLoadedPlugins();
        //Manager::getInstance()->activatePlugin('LanguagesManager');

        $config = Config::getInstance();
        unset($config->General['installation_in_progress']);
        unset($config->database['adapter']);

        $config->forceSave();
        // Put in Activated plugins
        Manager::getInstance()->loadActivatedPlugins();
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

                var_dump('foo');
                echo exec(
                    "php " . PIWIK_DOCUMENT_ROOT . "/console plugin:activate "
                        . $plugin_txt
                ) . "\n";
                $config->PluginsInstalled[] = $plugin_txt;
                $config->Plugins[] = $plugin_txt;
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
}
