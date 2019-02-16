<?php

  namespace Piwik\Plugins\Install\Lib;

  use Piwik\ErrorHandler;
  use Piwik\ExceptionHandler;
  use Piwik\FrontController;
  use Piwik\Access;
  use Piwik\Common;
  use Piwik\Plugins\UsersManager\API as APIUsersManager;
  use Piwik\Plugins\SitesManager\API as APISitesManager;
  use Piwik\Config;
  use Piwik\Filesystem;
  use Piwik\DbHelper;
  use Piwik\Updater;
  use Piwik\Plugin\Manager;
  use Piwik\Container\StaticContainer;
  use Piwik\Option;
  use Piwik\Plugin\ConsoleCommand;
  use Symfony\Component\Console\Output\OutputInterface;

  class Install {

    protected $config;

    public function __construct($config) {
      $this->config = $config;
    }

    public function execute() {
        $this->log('Running Configure Script');
        $this->deleteCache();
    }

    protected function log($text) {
        echo "$text\n";
      }

    /**
     * Performs the initial table creation for Matomo, if needed
     */
    protected function tableCreation() {
        $this->log('Ensuring Tables are Created');
        $tablesInstalled = DbHelper::getTablesInstalled();
        if (count($tablesInstalled) === 0) {
            echo "no tables exists";
          //DbHelper::createTables();
          //DbHelper::createAnonymousUser();
          //$this->updateComponents();
        }
      }

    /**
     * Delete all cache data
     */
    private function deleteCache() {
        $this->log('Delete Cache');
        Filesystem::deleteAllCacheOnUpdate();
      }
    private function isMatomoInstalled() {

    }
  }
