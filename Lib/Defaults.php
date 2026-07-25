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
        return getenv('MATOMO_DB_HOST') ?: getenv('MATOMO_DATABASE_HOST') ?: '';
    }

    /**
     * @return string
     */
    public function dbPort()
    {
        return getenv('MATOMO_DB_PORT') ?: getenv('MATOMO_DATABASE_PORT') ?: '3306';
    }

    /**
     * @return string
     */
    public function dbName()
    {
        return getenv('MATOMO_DB_NAME') ?: getenv('MATOMO_DATABASE_DBNAME') ?: '';
    }

    /**
     * @return string
     */
    public function dbPrefix()
    {
        return getenv('MATOMO_DB_PREFIX') ?: getenv('MATOMO_DATABASE_TABLES_PREFIX') ?: '';
    }

    /**
     * @return string
     */
    public function dbAdapter()
    {
        return getenv('MATOMO_DATABASE_ADAPTER') ?: 'PDO\MYSQL';
    }

    /**
     * @return string
     */
    public function dbPass()
    {
        return getenv('MATOMO_DB_PASSWORD') ?: getenv('MATOMO_DATABASE_PASSWORD') ?: '';
    }

    /**
     * @return string
     */
    public function dbUser()
    {
        return getenv('MATOMO_DB_USERNAME') ?: getenv('MATOMO_DATABASE_USERNAME') ?: '';
    }

    /**
     * @return string
     */
    public function dbCollation()
    {
        return getenv('MATOMO_DB_COLLATION') ?: getenv('MATOMO_DATABASE_COLLATION') ?: 'utf8mb4_general_ci';
    }

    /**
     * @return string
     */
    public function dbCharset()
    {
        return getenv('MATOMO_DB_CHARSET') ?: getenv('MATOMO_DATABASE_CHARSET') ?: 'utf8mb4';
    }

    /**
     * @return string
     */
    public function dbEnableSsl()
    {
        return getenv('MATOMO_DATABASE_ENABLE_SSL') ?: '';
    }

    /**
     * @return string
     */
    public function dbSslCa()
    {
        return getenv('MATOMO_DATABASE_SSL_CA') ?: '';
    }

    /**
     * @return string
     */
    public function dbSslCert()
    {
        return getenv('MATOMO_DATABASE_SSL_CERT') ?: '';
    }

    /**
     * @return string
     */
    public function dbSslKey()
    {
        return getenv('MATOMO_DATABASE_SSL_KEY') ?: '';
    }

    /**
     * @return string
     */
    public function dbSslCaPath()
    {
        return getenv('MATOMO_DATABASE_SSL_CA_PATH') ?: '';
    }

    /**
     * @return string
     */
    public function dbSslCipher()
    {
        return getenv('MATOMO_DATABASE_SSL_CIPHER') ?: '';
    }

    /**
     * @return string
     */
    public function dbSslNoVerify()
    {
        return getenv('MATOMO_DATABASE_SSL_NO_VERIFY') ?: '';
    }

    /**
     * @return string
     */
    public function firstSiteUrl()
    {
        return getenv('MATOMO_FIRST_SITE_URL') ?: '';
    }

    /**
     * @return string
     */
    public function firstSiteName()
    {
        return getenv('MATOMO_FIRST_SITE_NAME') ?: '';
    }

    /**
     * @return string
     */
    public function firstSiteUserPass()
    {
        return getenv('MATOMO_FIRST_USER_PASSWORD') ?: '';
    }

    /**
     * @return string
     */
    public function firstSiteUserEmail()
    {
        return getenv('MATOMO_FIRST_USER_EMAIL') ?: '';
    }

    /**
     * @return string
     */
    public function firstSiteUserName()
    {
        return getenv('MATOMO_FIRST_USER_NAME') ?: '';
    }

    /**
     * @return string
     */
    public function plugins()
    {
        return getenv('MATOMO_PLUGINS') ?: '';
    }

    /**
     * @return bool|false|string
     */
    public function timestamp()
    {
        return getenv('MATOMO_LOG_TIMESTAMP') ?: false;
    }
}
