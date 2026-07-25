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
    public function dbCollation()
    {
        $collation = 'utf8mb4_general_ci';
        if (getenv('MATOMO_DATABASE_COLLATION')) {
            $host = getenv('MATOMO_DATABASE_COLLATION');
        }
        if (getenv('MATOMO_DB_COLLATION')) {
            $host = getenv('MATOMO_DB_COLLATION');
        }
        return $collation;
    }

    /**
     * @return string
     */
    public function dbCharset()
    {
        $charset = 'utf8mb4';
        if (getenv('MATOMO_DATABASE_CHARSET')) {
            $host = getenv('MATOMO_DATABASE_CHARSET');
        }
        if (getenv('MATOMO_DB_CHARSET')) {
            $host = getenv('MATOMO_DB_CHARSET');
        }
        return $charset;
    }


    /**
     * @return string
     */
    public function dbEnableSsl()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_ENABLE_SSL')) {
            $value = getenv('MATOMO_DATABASE_ENABLE_SSL');
        }
        return $value;
    }

    /**
     * @return string
     */
    public function dbSslCa()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_SSL_CA')) {
            $value = getenv('MATOMO_DATABASE_SSL_CA');
        }
        return $value;
    }

    /**
     * @return string
     */
    public function dbSslCert()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_SSL_CERT')) {
            $value = getenv('MATOMO_DATABASE_SSL_CERT');
        }
        return $value;
    }

    /**
     * @return string
     */
    public function dbSslKey()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_SSL_KEY')) {
            $value = getenv('MATOMO_DATABASE_SSL_KEY');
        }
        return $value;
    }

    /**
     * @return string
     */
    public function dbSslCaPath()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_SSL_CA_PATH')) {
            $value = getenv('MATOMO_DATABASE_SSL_CA_PATH');
        }
        return $value;
    }

    /**
     * @return string
     */
    public function dbSslCipher()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_SSL_CIPHER')) {
            $value = getenv('MATOMO_DATABASE_SSL_CIPHER');
        }
        return $value;
    }

    /**
     * @return string
     */
    public function dbSslNoVerify()
    {
        $value = '';
        if (getenv('MATOMO_DATABASE_SSL_NO_VERIFY')) {
            $value = getenv('MATOMO_DATABASE_SSL_NO_VERIFY');
        }
        return $value;
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
