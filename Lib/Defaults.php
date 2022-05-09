<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Defaults
{
    /**
     * @return string
     */
    public function dbHost()
    {
        $host = '';
        if (getenv('MATOMO_DATABASE_HOST')) {
            $host = getenv('MATOMO_DATABASE_HOST');
        }
        if (getenv('MATOMO_DB_HOST')) {
            $host = getenv('MATOMO_DB_HOST');
        }
        return $host;
    }

    /**
     * @return string
     */
    public function dbPort()
    {
        $port = '3306';
        if (getenv('MATOMO_DATABASE_PORT')) {
            $port = getenv('MATOMO_DATABASE_PORT');
        }
        if (getenv('MATOMO_DB_PORT')) {
            $port = getenv('MATOMO_DB_PORT');
        }
        return $port;
    }

    /**
     * @return string
     */
    public function dbName()
    {
        $name = '';
        if (getenv('MATOMO_DATABASE_DBNAME')) {
            $name = getenv('MATOMO_DATABASE_DBNAME');
        }
        if (getenv('MATOMO_DB_NAME')) {
            $name = getenv('MATOMO_DB_NAME');
        }
        return $name;
    }

    /**
     * @return string
     */
    public function dbPrefix()
    {
        $prefix = '';
        if (getenv('MATOMO_DATABASE_TABLES_PREFIX')) {
            $prefix = getenv('MATOMO_DATABASE_TABLES_PREFIX');
        }
        if (getenv('MATOMO_DB_PREFIX')) {
            $prefix = getenv('MATOMO_DB_PREFIX');
        }
        return $prefix;
    }

    /**
     * @return string
     */
    public function dbAdapter()
    {
        $adapter = 'PDO\MYSQL';
        if (getenv('MATOMO_DATABASE_ADAPTER')) {
            $adapter = getenv('MATOMO_DATABASE_ADAPTER');
        }
        return $adapter;
    }


    /**
     * @return string
     */
    public function dbPass()
    {
        $pass = '';
        if (getenv('MATOMO_DATABASE_PASSWORD')) {
            $pass = getenv('MATOMO_DATABASE_PASSWORD');
        }
        if (getenv('MATOMO_DB_PASSWORD')) {
            $pass = getenv('MATOMO_DB_PASSWORD');
        }
        return $pass;
    }

    /**
     * @return string
     */
    public function dbUser()
    {
        $user = '';
        if (getenv('MATOMO_DATABASE_USERNAME')) {
            $user = getenv('MATOMO_DATABASE_USERNAME');
        }
        if (getenv('MATOMO_DB_USERNAME')) {
            $user = getenv('MATOMO_DB_USERNAME');
        }
        return $user;
    }

    /**
     * @return string
     */
    public function firstSiteUrl()
    {
        $url = '';
        if (getenv('MATOMO_FIRST_SITE_URL')) {
            $url = getenv('MATOMO_FIRST_SITE_URL');
        }
        return $url;
    }

    /**
     * @return string
     */
    public function firstSiteName()
    {
        $name = '';
        if (getenv('MATOMO_FIRST_SITE_NAME')) {
            $name = getenv('MATOMO_FIRST_SITE_NAME');
        }
        return $name;
    }

    /**
     * @return string
     */
    public function firstSiteUserPass()
    {
        $pass = '';
        if (getenv('MATOMO_FIRST_USER_PASSWORD')) {
            $pass = getenv('MATOMO_FIRST_USER_PASSWORD');
        }
        return $pass;
    }


    /**
     * @return string
     */
    public function firstSiteUserEmail()
    {
        $email = '';
        if (getenv('MATOMO_FIRST_USER_EMAIL')) {
            $email = getenv('MATOMO_FIRST_USER_EMAIL');
        }
        return $email;
    }

    /**
     * @return string
     */
    public function firstSiteUserName()
    {
        $name = '';
        if (getenv('MATOMO_FIRST_USER_NAME')) {
            $name = getenv('MATOMO_FIRST_USER_NAME');
        }
        return $name;
    }


    /**
     * @return string
     */
    public function plugins()
    {
        $plugins = '';
        if (getenv('MATOMO_PLUGINS')) {
            $plugins = getenv('MATOMO_PLUGINS');
        }
        return $plugins;
    }


    /**
     * @return bool|false|string
     */
    public function timestamp()
    {
        $timestamp = false;
        if (getenv('MATOMO_LOG_TIMESTAMP')) {
            $timestamp = getenv('MATOMO_LOG_TIMESTAMP');
        }
        return $timestamp;
    }
}
