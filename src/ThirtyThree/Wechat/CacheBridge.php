<?php

namespace ThirtyThree\Wechat;

use Illuminate\Support\Facades\Cache;
use Doctrine\Common\Cache\Cache as CacheInterface;

/**
 * Cache bridge for laravel.
 */
class CacheBridge implements CacheInterface
{
    /**
     * Fetches an entry from the cache.
     *
     * @param string $id the id of the cache entry to fetch
     *
     * @return mixed the cached data or FALSE, if no cache entry exists for the given id
     */
    public function fetch($id)
    {
        return Cache::get($id);
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id the cache id of the entry to check for
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise
     */
    public function contains($id)
    {
        return Cache::has($id);
    }

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $id       the cache id
     * @param mixed  $data     the cache entry/data
     * @param int    $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($lifeTime == 0) {
            return Cache::forever($id, $data);
        }

        return Cache::put($id, $data, $lifeTime / 60);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id the cache id
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    public function delete($id)
    {
        return Cache::forget($id);
    }

    /**
     * Retrieves cached information from the data store.
     *
     * @return array|null an associative array with server's statistics if available, NULL otherwise
     */
    public function getStats()
    {
    }
}
