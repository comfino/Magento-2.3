<?php

namespace Comfino\Update;

use Comfino\PluginShared\CacheManager;

class UpdateManager
{
    private const GITHUB_REPOSITORY = 'comfino/Magento-2.3';
    private const GITHUB_URL = 'https://github.com/' . self::GITHUB_REPOSITORY;
    private const GITHUB_API_URL = 'https://api.github.com/repos/' . self::GITHUB_REPOSITORY;
    private const CACHE_KEY = 'comfino_github_version_check';
    private const CACHE_TTL = 86400; // 24 hours
    private const CONNECT_TIMEOUT = 5;
    private const TRANSFER_TIMEOUT = 10;

    /**
     * Check for available updates on GitHub.
     *
     * @param string $currentVersion
     * @return array{
     *     update_available: bool,
     *     current_version: string,
     *     github_version?: string,
     *     release_notes_url?: string,
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
            $client = new \ComfinoExternal\Sunrise\Http\Client\Curl\Client(
                new \ComfinoExternal\Sunrise\Http\Factory\ResponseFactory(),
                [CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT, CURLOPT_TIMEOUT => self::TRANSFER_TIMEOUT]
            );

            $request = (new \ComfinoExternal\Sunrise\Http\Factory\RequestFactory())
                ->createRequest('GET', self::GITHUB_API_URL . '/releases/latest')
                ->withHeader('Accept', 'application/vnd.github.v3+json')
                ->withHeader('User-Agent', 'Comfino-Magento-Plugin/' . $currentVersion);

            $response = $client->sendRequest($request);
        } catch (\Throwable $e) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'error' => 'Failed to connect to GitHub: ' . $e->getMessage(),
                'checked_at' => time(),
            ];
        }

        if ($response->getStatusCode() !== 200) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'error' => 'Failed to fetch release information from GitHub.',
                'checked_at' => time(),
            ];
        }

        $response->getBody()->rewind();
        $releaseInfo = json_decode($response->getBody()->getContents(), true);

        if (!is_array($releaseInfo) || !isset($releaseInfo['tag_name'])) {
            return [
                'update_available' => false,
                'current_version' => $currentVersion,
                'error' => 'Invalid GitHub API response.',
                'checked_at' => time(),
            ];
        }

        $githubVersion = ltrim($releaseInfo['tag_name'], 'v');

        return [
            'update_available' => version_compare($githubVersion, $currentVersion, '>'),
            'current_version' => $currentVersion,
            'github_version' => $githubVersion,
            'release_notes_url' => $releaseInfo['html_url'] ?? self::GITHUB_URL . '/releases',
            'checked_at' => time(),
        ];
    }
}
