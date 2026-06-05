<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;

final class ShareCountService
{
    /**
     * @param SocialShareSettings $settings
     * @param CacheInterface|null $cache
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Qubus\Exception\Data\TypeException
     * @throws \ReflectionException
     */
    public function __construct(
        private readonly SocialShareSettings $settings,
        private ?CacheInterface $cache = null,
    ) {
        $this->cache = $cache ?? SimpleCacheObjectCacheFactory::make('socialshare');
    }

    /**
     * @param string $url
     * @param array $networks
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
     */
    public function getCounts(string $url, array $networks): array
    {
        $settings = $this->settings->all();

        if (!$settings['show_counts'] && !$settings['show_total']) {
            return [];
        }

        $cacheKey = 'social_share_counts_' . sha1($url);

        if ($settings['cache_counts']) {
            $cached = $this->cache->get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $counts = [];

        foreach ($networks as $network) {
            $counts[$network] = 0;
        }

        $counts['total'] = array_sum($counts);

        if ($settings['cache_counts']) {
            $this->cache->set($cacheKey, $counts, (int) $settings['cache_ttl']);
        }

        return $counts;
    }
}
