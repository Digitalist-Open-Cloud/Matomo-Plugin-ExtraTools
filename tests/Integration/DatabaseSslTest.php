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

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\ExtraTools\Lib\DatabaseSsl;

/**
 * @group ExtraTools
 * @group Plugins
 * @group DatabaseSsl
 */
class DatabaseSslTest extends TestCase
{
    public function testFromDatabaseConfigPicksOnlyNonEmptySslKeys()
    {
        $database = [
            'host' => 'localhost',
            'username' => 'root',
            'enable_ssl' => '1',
            'ssl_ca' => '/etc/ssl/certs/ca.crt',
            'ssl_cipher' => '',
            'ssl_no_verify' => '1',
        ];

        $ssl = DatabaseSsl::fromDatabaseConfig($database);

        $this->assertSame(
            [
                'enable_ssl' => '1',
                'ssl_ca' => '/etc/ssl/certs/ca.crt',
                'ssl_no_verify' => '1',
            ],
            $ssl
        );
    }

    public function testIsEnabled()
    {
        $this->assertFalse(DatabaseSsl::isEnabled([]));
        $this->assertFalse(DatabaseSsl::isEnabled(['enable_ssl' => '0']));
        $this->assertFalse(DatabaseSsl::isEnabled(['enable_ssl' => 0]));
        $this->assertTrue(DatabaseSsl::isEnabled(['enable_ssl' => '1']));
        $this->assertTrue(DatabaseSsl::isEnabled(['enable_ssl' => 1]));
    }

    public function testSslOptionLinesEmptyWhenDisabled()
    {
        $this->assertSame('', DatabaseSsl::sslOptionLines([]));
        $this->assertSame('', DatabaseSsl::sslOptionLines(['enable_ssl' => '0']));
    }

    public function testSslOptionLinesNoVerify()
    {
        $lines = DatabaseSsl::sslOptionLines([
            'enable_ssl' => '1',
            'ssl_ca' => '/etc/ssl/certs/ca.crt',
            'ssl_no_verify' => '1',
        ]);

        $this->assertStringContainsString('loose-ssl', $lines);
        $this->assertStringContainsString('ssl-ca=/etc/ssl/certs/ca.crt', $lines);
        $this->assertStringContainsString('loose-ssl-mode=REQUIRED', $lines);
        // No certificate verification when ssl_no_verify is set.
        $this->assertStringNotContainsString('VERIFY_CA', $lines);
        $this->assertStringNotContainsString('ssl-verify-server-cert', $lines);
    }

    public function testSslOptionLinesVerifyByDefault()
    {
        $lines = DatabaseSsl::sslOptionLines([
            'enable_ssl' => '1',
            'ssl_ca' => '/etc/ssl/certs/ca.crt',
        ]);

        $this->assertStringContainsString('loose-ssl-mode=VERIFY_CA', $lines);
        $this->assertStringContainsString('loose-ssl-verify-server-cert', $lines);
        $this->assertStringNotContainsString('REQUIRED', $lines);
    }

    public function testSslOptionLinesMapsAllKeys()
    {
        $lines = DatabaseSsl::sslOptionLines([
            'enable_ssl' => '1',
            'ssl_ca' => '/ca',
            'ssl_cert' => '/cert',
            'ssl_key' => '/key',
            'ssl_ca_path' => '/capath',
            'ssl_cipher' => 'DHE-RSA-AES256-SHA',
        ]);

        $this->assertStringContainsString('ssl-ca=/ca', $lines);
        $this->assertStringContainsString('ssl-cert=/cert', $lines);
        $this->assertStringContainsString('ssl-key=/key', $lines);
        $this->assertStringContainsString('ssl-capath=/capath', $lines);
        $this->assertStringContainsString('ssl-cipher=DHE-RSA-AES256-SHA', $lines);
    }

    public function testCreateClientOptionFileContainsCredentialsAndSsl()
    {
        [$handle, $path] = DatabaseSsl::createClientOptionFile([
            'db_user' => 'bob',
            'db_pass' => 'secret',
            'enable_ssl' => '1',
            'ssl_ca' => '/etc/ssl/certs/ca.crt',
            'ssl_no_verify' => '1',
        ]);

        $contents = file_get_contents($path);

        $this->assertStringContainsString('[client]', $contents);
        $this->assertStringContainsString('user=bob', $contents);
        $this->assertStringContainsString('password=secret', $contents);
        $this->assertStringContainsString('ssl-ca=/etc/ssl/certs/ca.crt', $contents);
        $this->assertStringContainsString('loose-ssl-mode=REQUIRED', $contents);

        fclose($handle);
        // Closing the handle removes the temp file.
        $this->assertFileDoesNotExist($path);
    }

    public function testCreateClientOptionFileWithoutSslHasNoSslLines()
    {
        [$handle, $path] = DatabaseSsl::createClientOptionFile([
            'db_user' => 'bob',
            'db_pass' => 'secret',
        ]);

        $contents = file_get_contents($path);

        $this->assertStringContainsString('user=bob', $contents);
        $this->assertStringContainsString('password=secret', $contents);
        $this->assertStringNotContainsString('ssl-', $contents);
        $this->assertStringNotContainsString('loose-ssl', $contents);

        fclose($handle);
    }
}
