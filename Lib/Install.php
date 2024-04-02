<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Piwik\Filesystem;
use Piwik\DbHelper;
use Piwik\Db;
use Piwik\Db\Schema;
use Piwik\Config;
use Piwik\Common;
use Piwik\Access;
use Piwik\Updater;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\Plugin\Manager;
use Piwik\Version;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\Marketplace\LicenseKey;
use Piwik\Plugins\TagManager\Dao\ContainersDao;
use Piwik\Plugins\ExtraTools\Lib\Site;

class Install
{
    protected $config;
    protected $fileconfig;
    protected $options;
    protected $timestamp;
    protected $user;
    protected $licensekey;
    public bool $silent;

    /**
     * @var APIUsersManager
     */
    private $api;

   /**
    * @var OutputInterface
    */
    private $output;


    public function __construct(
        $options,
        OutputInterface $output,
        $fileconfig = null,
        $user = null,
        $silent = 0
    ) {
        $this->config = Config::getInstance();
        $this->options = $options;
        $this->output = $output;
        $this->fileconfig = $fileconfig;
        $this->timestamp = false;
        $this->user = $user;
        $this->licensekey = getenv('MATOMO_LICENSE');
        $this->silent = $silent;
    }

    public function execute()
    {
        $options = $this->options;
        if (isset($options['timestamp'])) {
            if ($options['timestamp'] == true) {
                $this->timestamp = true;
            }
        }

        $fileconfig = false;

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
            $first_site_url = $fileconfig['first-site-url'];
        }
        $dontdrobdb = $options['do-not-drop-db'];
        $this->silent = $options['silent'];

        if ($this->silent !== true) {
            $this->output->writeln("<info>Starting <comment>install</comment></info>");
        }


        # Always disable sending emails at install
        $this->config->General['emails_enabled'] = 0;
        $this->config->General['maintenance_mode'] = 1;
        $this->deleteCache();
        $this->initDBConnection();
        $this->tableCreation();
        $this->saveLanguage('en');
        $this->createSuperUser();
        $this->installPlugins();
        $this->unInstallPlugins();
        $this->writeConfig();
        $this->finish();
        $this->saveLicenseKey();
        $this->login();
    }



    protected function saveLicenseKey()
    {
        $license = new LicenseKey();
        $license->set($this->licensekey);
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
            if (isset($options['db-pass'])) {
                $config->database['password'] = $options['db-pass'];
            }
            if (isset($options['db-host'])) {
                $config->database['host'] = $options['db-host'];
            }
            if (isset($options['db-port'])) {
                $config->database['port'] = $options['db-port'];
            }
            if (isset($options['db-name'])) {
                $config->database['dbname'] = $options['db-name'];
            }
            if (isset($options['db-prefix'])) {
                $config->database['tables_prefix'] = $options['db-prefix'];
            }
            if (isset($options['db-adapter'])) {
                $config->database['adapter'] = $options['db-adapter'];
            }
        }

        if (isset($fileconfig)) {
            if (isset($fileconfig['database'])) {
                $database = $fileconfig['database'];
                $keys = [
                    'tables_prefix',
                    'host',
                    'port',
                    'username',
                    'password',
                    'dbname',
                    'adapter',
                ];
                foreach ($keys as $key) {
                    if (isset($database[$key])) {
                        $config->database[$key] = $database[$key];
                    }
                }
            }
        }


        if ($this->silent !== true) {
            $this->log('Initialising Database Connections');
        }


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
                DbHelper::getDefaultCharset();
                break;
            } catch (\Exception $e) {
                $this->log(
                    "Database connection failed. Retrying in $retry_timeout seconds."
                );
                $this->log($e->getMessage());
                sleep($retry_timeout);
            }
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
        if ($this->silent === true) {
            return 0;
        } else {
            $datestamp = '';
            if ($this->timestamp == true) {
                $datestamp = '[' . date("Y-m-d H:i:s") . '] ';
            }
            $this->output->writeln("<info>$datestamp$text</info>");
        }
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
                $username = '';
                $pass = '';
                $email = '';
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
        if ($this->silent !== true) {
            $this->output->writeln("<info>Deleting <comment>cache</comment></info>");
        }

        Filesystem::deleteAllCacheOnUpdate();
    }


    protected function installPlugins()
    {

        $config = $this->config;
        $installed_plugins = $config->PluginsInstalled;
        $fileconfig = false;
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
                $install_tag_manager = false;
                foreach ($installplugins as $plugin) {
                    if ($plugin == 'TagManager') {
                            $install_tag_manager = true;
                            unset($plugin);
                    }
                    if (isset($plugin)) {
                        Manager::getInstance()->activatePlugin($plugin);
                        $this->log("Activated $plugin");
                    }
                }
                Manager::getInstance()->loadPluginTranslations();
                Manager::getInstance()->loadActivatedPlugins();
                Manager::getInstance()->installLoadedPlugins();
                $config->PluginsInstalled = $installplugins;
                if ($install_tag_manager == true) {
                    $dao = new ContainersDao();
                    $dao->install();
                    $this->log("Activated TagManager (needs to be activated in UI)");
                }
            }
        }
    }


    protected function unInstallPlugins()
    {

        $config = $this->config;
        $pluginsInstalled = $config->PluginsInstalled;

        CliManager::getInstance()->loadActivatedPlugins();
        $activated = CliManager::getInstance()->getActivatedPlugins();

        $all_active = array_unique(array_merge($pluginsInstalled, $activated), SORT_REGULAR);

        $fileconfig = false;
        if (isset($this->fileconfig)) {
            $config_from_file = $this->fileconfig;
            if (isset($config_from_file->Config)) {
                $fileconfig = $config_from_file->Config;
            }
            if (isset($fileconfig['PluginsUnInstalled'])) {
                $uninstallplugins = $fileconfig['PluginsUnInstalled'];
            }
            if (isset($uninstallplugins)) {
                foreach ($uninstallplugins as $plugin) {
                    if (in_array($plugin, $all_active)) {
                        CliManager::getInstance()->deactivatePlugin($plugin);
                        CliManager::getInstance()->uninstallPlugin($plugin);
                        $this->log("Deactivated $plugin");
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected function writeConfig()
    {
        $fileconfig = false;
        if (isset($this->fileconfig)) {
            $config_from_file = $this->fileconfig;
            if (isset($config_from_file->Config)) {
                $fileconfig = $config_from_file->Config;
                $configarray =  $fileconfig['Config'];
                if (isset($configarray)) {
                    foreach ($configarray as $key => $values) {
                        foreach ($values as $setting => $value) {
                            $config_write = new ConfigManipulation($this->config, $this->output);
                            $config_write->saveConfig("$key", "$setting", "$value");
                        }
                    }
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
            $config->General['installation_in_progress']
        );
        $this->config->General['maintenance_mode'] = 0;

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
     * Save language selection in session-store
     */
    public function saveLanguage($lang)
    {
        $language = $lang;
        LanguagesManager::setLanguageForSession($language);
    }


    private function login()
    {
        $username = '';
        $pass = '';
        extract($this->user);
        $this->log("Now you can login with user <comment>$username</comment> and password <comment>$pass</comment>");
    }
}
