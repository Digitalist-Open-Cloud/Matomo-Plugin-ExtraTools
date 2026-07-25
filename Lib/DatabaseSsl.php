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

/**
 * Helper for using SSL/TLS with the mysql command line tools
 * (mysql, mysqldump, mysqladmin).
 *
 * The SSL settings mirror the keys Matomo itself understands in the
 * [database] section of config.ini.php:
 *
 *   enable_ssl, ssl_key, ssl_cert, ssl_ca, ssl_ca_path, ssl_cipher,
 *   ssl_no_verify
 *
 * Instead of passing credentials and SSL flags on the command line (which
 * leaks the password to the process list and differs between the MySQL and
 * MariaDB clients) we write them to a temporary option file that is handed to
 * the client via --defaults-extra-file. Client specific options use the
 * "loose-" prefix so a client that does not recognise an option ignores it
 * rather than aborting.
 */
class DatabaseSsl
{
    /**
     * SSL related keys as used in Matomo's [database] config section.
     */
    public const SSL_KEYS = [
        'enable_ssl',
        'ssl_key',
        'ssl_cert',
        'ssl_ca',
        'ssl_ca_path',
        'ssl_cipher',
        'ssl_no_verify',
    ];

    /**
     * Pick the SSL related keys out of a Matomo [database] config array.
     *
     * @param array $database The [database] config section.
     * @return array Only the SSL keys that are actually set.
     */
    public static function fromDatabaseConfig(array $database): array
    {
        $ssl = [];
        foreach (self::SSL_KEYS as $key) {
            if (isset($database[$key]) && $database[$key] !== '') {
                $ssl[$key] = $database[$key];
            }
        }
        return $ssl;
    }

    /**
     * Whether SSL is enabled in the given config array.
     *
     * @param array $config
     * @return bool
     */
    public static function isEnabled(array $config): bool
    {
        return !empty($config['enable_ssl']);
    }

    /**
     * Build a temporary mysql option file ([client] group) holding the
     * credentials and, when enabled, the SSL settings.
     *
     * Keep the returned resource referenced until the process has finished;
     * closing it deletes the temp file.
     *
     * @param array $config Config array with db_user, db_pass and the SSL keys.
     * @return array{0: resource, 1: string} [handle, path]
     */
    public static function createClientOptionFile(array $config): array
    {
        $temp = tmpfile();
        $contents = "[client]\n"
            . 'user=' . ($config['db_user'] ?? '') . "\n"
            . 'password=' . ($config['db_pass'] ?? '') . "\n";
        $contents .= self::sslOptionLines($config);
        fwrite($temp, $contents);
        $path = stream_get_meta_data($temp)['uri'];
        return [$temp, $path];
    }

    /**
     * Build the SSL lines for a mysql [client] option group.
     *
     * @param array $config Config array with the SSL keys.
     * @return string Lines terminated by a newline, or an empty string when
     *                SSL is disabled.
     */
    public static function sslOptionLines(array $config): string
    {
        if (!self::isEnabled($config)) {
            return '';
        }

        $lines = [];
        // Enable SSL on MariaDB even when no CA/cert is supplied. MySQL 8+
        // does not know the bare "ssl" option and, thanks to "loose-",
        // simply ignores it and relies on ssl-mode below instead.
        $lines[] = 'loose-ssl';

        $map = [
            'ssl_ca'      => 'ssl-ca',
            'ssl_cert'    => 'ssl-cert',
            'ssl_key'     => 'ssl-key',
            'ssl_ca_path' => 'ssl-capath',
            'ssl_cipher'  => 'ssl-cipher',
        ];
        foreach ($map as $configKey => $option) {
            if (!empty($config[$configKey])) {
                $lines[] = $option . '=' . $config[$configKey];
            }
        }

        if (!empty($config['ssl_no_verify'])) {
            // Require encryption but do not verify the server certificate.
            $lines[] = 'loose-ssl-mode=REQUIRED';        // MySQL
            // MariaDB: absence of ssl-verify-server-cert means no verification.
        } else {
            $lines[] = 'loose-ssl-mode=VERIFY_CA';       // MySQL
            $lines[] = 'loose-ssl-verify-server-cert';   // MariaDB
        }

        return implode("\n", $lines) . "\n";
    }
}
