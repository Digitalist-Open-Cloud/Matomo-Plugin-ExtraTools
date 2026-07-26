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

namespace Piwik\Plugins\ExtraTools\Commands;

use Piwik\Cache;
use Piwik\Config;
use Piwik\Plugin\ConsoleCommand;

/**
 * Clears Matomo's cache entries from an external (Redis) cache backend.
 *
 * Matomo's own `cache:clear` flushes the configured cache backend via
 * `FLUSHDB`, which many managed/shared Redis setups disable or restrict, and
 * which would also wipe out any unrelated keys in a Redis database shared
 * with other applications. This command instead scans for and deletes only
 * Matomo's own cache keys.
 */
class ClearExternalCache extends ConsoleCommand
{
    /**
     * Key prefixes used by Matomo's Redis-backed caches: the Lazy cache
     * (general/tracker/UI cache, see Matomo\Cache\Lazy) and the Eager cache
     * (see config/global.php and Matomo\Cache\Eager).
     */
    private const KEY_PATTERNS = ['matomocache_*', 'eagercache-*'];

    private const SCAN_COUNT = 1000;

    protected function configure()
    {
        $HelpText = 'The <info>%command.name%</info> command clears Matomo\'s cache entries from
the configured external (Redis) cache, without running <comment>FLUSHDB</comment>.

This is useful when the Redis server is shared with other applications, or
when the hosting provider disables/restricts <comment>FLUSHDB</comment>/<comment>FLUSHALL</comment>, both of
which can make the regular <info>cache:clear</info> command appear to do nothing.

<comment>Samples:</comment>
<info>%command.name%</info>';
        $this->setHelp($HelpText);
        $this->setName('extra:clear-external-cache');
        $this->setDescription('Clears Matomo\'s cache entries from the external (Redis) cache backend.');
    }

    protected function doExecute(): int
    {
        $output = $this->getOutput();

        if (!$this->isRedisConfiguredAsCacheBackend()) {
            $output->writeln('<comment>Redis is not the configured cache backend, nothing to clear.</comment>');
            return self::SUCCESS;
        }

        try {
            $redis = Cache::buildBackend('redis')->getRedis();
            // Ensures scan() keeps iterating internally until it has a batch of
            // keys (or the cursor is exhausted) instead of returning early.
            $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);

            $deleted = 0;
            foreach (self::KEY_PATTERNS as $pattern) {
                $deleted += $this->deleteKeysMatching($redis, $pattern);
            }
        } catch (\RedisException | \InvalidArgumentException $e) {
            $this->writeErrorMessage('Could not clear the external cache: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->writeSuccessMessage("Cleared {$deleted} key(s) from the external cache.");
        return self::SUCCESS;
    }

    /**
     * True if the `[Cache] backend` is `redis` directly, or `chained` with
     * `redis` listed among `[ChainedCache] backends`.
     */
    private function isRedisConfiguredAsCacheBackend(): bool
    {
        $backend = Config::getInstance()->Cache['backend'] ?? '';

        if ($backend === 'redis') {
            return true;
        }

        if ($backend === 'chained') {
            $chainedBackends = Config::getInstance()->ChainedCache['backends'] ?? [];
            return in_array('redis', (array) $chainedBackends, true);
        }

        return false;
    }

    private function deleteKeysMatching(\Redis $redis, string $pattern): int
    {
        $deleted = 0;
        $cursor = null;

        do {
            $keys = $redis->scan($cursor, $pattern, self::SCAN_COUNT);
            if (!empty($keys)) {
                $deleted += $redis->del($keys);
            }
        } while ($cursor !== 0);

        return $deleted;
    }
}
