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
    private const CACHE_TTL = 86400; // 24 hours

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
        $cacheItem = null;

        try {
            $cacheItem = CacheManager::getCachePool()->getItem(self::CACHE_KEY);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (\Throwable $e) {
            // Cache not available - proceed without it.
        }

        $result = self::fetchLatestRelease($currentVersion);

        try {
            if ($cacheItem !== null) {
                $cacheItem->set($result);
                $cacheItem->expiresAfter(self::CACHE_TTL);
                CacheManager::getCachePool()->save($cacheItem);
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
