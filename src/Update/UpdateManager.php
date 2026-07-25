<?php

namespace Comfino\Update;

use Comfino\Api\ApiClient;
use Comfino\PluginShared\CacheManager;

class UpdateManager
{
    /**
     * Base canonical platform slug polled on the Comfino release API. This is the legacy Magento 2.3 line; the API
     * resolves the concrete compatible line (2.3, PHP 7.4) from the client User-Agent.
     */
    private const PLATFORM = 'magento-2.3';
    private const CACHE_KEY = 'comfino_github_version_check';
    private const LOCK_KEY = 'comfino_github_version_check_lock';
    private const LOCK_TTL = 300; // 5 minutes
    /* Jittered ~1 day interval (20-28h): shops tend to install/upgrade around the same calendar moments, so a fixed
       24h TTL would make every installation re-check the shared release API at the same clustered hour indefinitely.
       Randomizing lets each installation's check hour drift day to day instead. */
    private const CACHE_TTL_MIN = 72000; // 20 hours
    private const CACHE_TTL_MAX = 100800; // 28 hours

    /**
     * Check for available updates via the Comfino release API.
     *
     * @param string $currentVersion
     * @return array{
     *     update_available: bool,
     *     current_version: string,
     *     github_version?: string,
     *     download_url?: string,
     *     release_notes_url?: string,
     *     description_html?: string,
     *     checked_at?: int,
     *     error?: string
     * }
     */
    public static function checkForUpdates(string $currentVersion): array
    {
        $cachePool = null;
        $cacheItem = null;

        try {
            $cachePool = CacheManager::getCachePool();
            $cacheItem = $cachePool->getItem(self::CACHE_KEY);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            /* Claim a short-lived exclusive lock before hitting the API. Magento's admin notification list polls
               registered messages on essentially every admin page load, so concurrent requests can each observe the
               cache as a miss before the first one writes its result back, firing duplicate release-check calls. */
            $lockItem = $cachePool->getItem(self::LOCK_KEY);

            if ($lockItem->isHit()) {
                return ['update_available' => false, 'current_version' => $currentVersion];
            }

            $lockItem->set(true);
            $lockItem->expiresAfter(self::LOCK_TTL);
            $cachePool->save($lockItem);
        } catch (\Throwable $e) {
            // Cache not available - proceed without it.
        }

        $result = self::fetchLatestRelease($currentVersion);

        try {
            if ($cachePool !== null && $cacheItem !== null) {
                $cacheItem->set($result);
                $cacheItem->expiresAfter(random_int(self::CACHE_TTL_MIN, self::CACHE_TTL_MAX));
                $cachePool->save($cacheItem);
            }
        } catch (\Throwable $e) {
            // Ignore cache save errors.
        }

        return $result;
    }

    private static function fetchLatestRelease(string $currentVersion): array
    {
        try {
            $release = ApiClient::getInstance()->getLatestPluginRelease(self::PLATFORM);
        } catch (\Throwable $e) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'error' => 'Failed to fetch release information from Comfino API: ' . $e->getMessage(),
                'checked_at' => time(),
            ];
        }

        if ($release === null) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'error' => 'No release information available from Comfino API.',
                'checked_at' => time(),
            ];
        }

        return [
            'update_available' => version_compare($release->version, $currentVersion, '>'),
            'current_version' => $currentVersion,
            'github_version' => $release->version,
            'download_url' => $release->downloadUrl,
            'release_notes_url' => $release->releaseUrl,
            'description_html' => $release->descriptionHtml,
            'checked_at' => time(),
        ];
    }
}
