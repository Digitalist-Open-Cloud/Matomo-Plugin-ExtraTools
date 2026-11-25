<?php

namespace Piwik\Plugins\ExtraTools\tests\Integration;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\ExtraTools\Lib\Backup;

/**
 * @group ExtraTools
 * @group Plugins
 * @group DatabaseBackup
 */
class BackupTest extends TestCase
{
    private $path;
    private $dir;

    public function setUp(): void
    {
        $this->path = getenv('PATH');
        $this->dir = sys_get_temp_dir() . '/extratools-clients-' . getmypid();
        mkdir($this->dir);
        // Only the clients of the test can be found.
        putenv('PATH=' . $this->dir);
    }

    public function tearDown(): void
    {
        putenv('PATH=' . $this->path);
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
    }

    public function testTheDumpClientOfMariaDbIsUsedWhereThereIsNoMysqldump()
    {
        $this->install('mariadb-dump');
        $this->assertSame($this->dir . '/mariadb-dump', Backup::findDumpClient());

        $this->install('mysqldump');
        $this->assertSame($this->dir . '/mysqldump', Backup::findDumpClient());
    }

    public function testABackupWithoutADumpClientSaysWhatIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('mariadb-dump');

        Backup::findDumpClient();
    }

    private function install(string $client): void
    {
        file_put_contents($this->dir . '/' . $client, "#!/bin/sh\n");
        chmod($this->dir . '/' . $client, 0755);
    }
}
