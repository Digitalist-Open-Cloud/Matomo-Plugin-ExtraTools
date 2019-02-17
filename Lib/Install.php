<?php

  namespace Piwik\Plugins\MatomoExtraTools\Lib;

  use Piwik\Filesystem;
  use Piwik\DbHelper;
  use Piwik\Db\Schema;
  use Symfony\Component\Console\Output\OutputInterface;

class Install
{

    protected $config;

    /**
       * @var OutputInterface
       */
    private $output;


    public function __construct($config, OutputInterface $output)
    {
        $this->config = $config;
        $this->output = $output;
    }

    public function execute()
    {
        $this->output->writeln("<info>Starting <comment>install</comment></info>");
        $this->deleteCache();
        $this->tableCreation();
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
        $tablesInstalled = DbHelper::getTablesInstalled();
        $numberOfTables = sizeof($tablesInstalled);
        $version = DbHelper::getInstallVersion();
        $db_name = Schema::getInstance()->


        if($numberOfTables >= 0) {

            $this->output->writeln("<info>  Database tables <comment>exists</comment> with $numberOfTables tables</info>");
            $this->output->writeln("<info>  Version installed is <comment>$version</comment></info>");
          //DbHelper::createTables();
          //DbHelper::createAnonymousUser();
          //$this->updateComponents();
        }
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
}
