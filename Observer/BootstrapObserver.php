<?php

namespace Comfino\ComfinoGateway\Observer;

use Comfino\PluginShared\CacheManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Initializes the shared Comfino CacheManager once, early in the request lifecycle - before any controller, block, or
 * observer reads the cache. Fired on `controller_action_predispatch`, which runs before every controller action in
 * all areas (frontend and adminhtml).
 *
 * Without this the admin config blocks that poll the release API (ComfinoLogo, SystemInfo) each initialized
 * CacheManager independently - or not at all. ComfinoLogo ran first while CacheManager was still uninitialized, so it
 * fell back to a throwaway per-request ArrayCachePool: its 24h release-check result was never persisted, and the
 * release endpoint was polled on every page render (and, when SystemInfo's own filesystem cache was cold, twice in
 * the same request). A single init here gives every consumer the same persistent, store-scoped filesystem pool.
 */
class BootstrapObserver implements ObserverInterface
{
    private DirectoryList $dirList;
    private StoreManagerInterface $storeManager;

    public function __construct(DirectoryList $dirList, StoreManagerInterface $storeManager)
    {
        $this->dirList = $dirList;
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer): void
    {
        try {
            /* Match ApiService's per-store scope so cached API responses never leak between store views with
               independent API keys, and so the release-check entry is shared across every code path in a request. */
            CacheManager::init($this->dirList->getPath('var'), (string) $this->storeManager->getStore()->getId());
        } catch (\Throwable $e) {
            /* An observer must never break request dispatch. If init fails, cache consumers transparently fall back
               to an in-memory pool. */
        }
    }
}