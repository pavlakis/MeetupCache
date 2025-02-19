<?php namespace ShaunHare\MeetupCache;

use DMS\Service\Meetup\MeetupKeyAuthClient;

use DMS\Service\Meetup\Response\SingleResultResponse;
use Stash\Pool;

/**
 *  A Meetup Service cache
 *
 *  A meetup service cache of sorts - enables us to get the response from meetup
 *  or from the filesystem if present
 *
 * @author Shaun Hare
 */
class MeetupCache
{

    /**
     * @var MeetupKeyAuthClient
     */
    private $client;

    /**
     * @var Pool
     */
    private $cache;

    /**
     * @var bool
     */
    private $fromCache;

    /**
     * ServiceProxy constructor.
     *
     * @param MeetupKeyAuthClient $client
     * @param Pool $cache
     */
    public function __construct(MeetupKeyAuthClient $client, Pool $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * @return Pool
     */
    public function getCachedItem($name)
    {
        return $this->cache->getItem($name)->get();
    }

    /**
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->fromCache;
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments = [])
    {
        if (!empty($arguments)) {
            $item = $this->cache->getItem($this->generateCachekey($name, $arguments[0]));
        } else {
            $item = $this->cache->getItem($name);
        }

        $meetupResponse = $item->get();

        if ($item->isMiss()) {
            $this->fromCache = false;
            $meetupResponse = $this->client->$name($arguments[0]);
            $this->cache->save($item->set($meetupResponse));
        }
        $this->fromCache = true;
        return $meetupResponse;
    }

    /**
     * expire the cache
     */
    public function expireCache(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Generate a cache key
     *
     * @param $name
     * @param $args
     * @return string
     */
    public function generateCachekey($name, $args = [])
    {
        $name = $name . join("", $args);
        return hash("sha256", $name);
    }
}
