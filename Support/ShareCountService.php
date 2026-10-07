<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Plugin\SocialShare\Repository\ShareCountRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\get_current_site_key;

final class ShareCountService
{
    private CacheInterface $cache;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Qubus\Exception\Data\TypeException
     * @throws \ReflectionException
     */
    public function __construct(
        private readonly SocialShareSettings $settings,
        private readonly ShareCountRepository $repository,
        ?CacheInterface $cache = null,
    ) {
        $this->cache = $cache ?? SimpleCacheObjectCacheFactory::make('socialshare');
    }

    /**
     * @param string $url
     * @param list<string> $networks
     * @param string|null $contentKey
     * @param bool $force
     * @return array<string, int>
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getCounts(
        string $url,
        array $networks,
        ?string $contentKey = null,
        bool $force = false
    ): array {
        $settings = $this->settings->all();

        if (!$force && !$settings['show_counts'] && !$settings['show_total']) {
            return [];
        }

        $siteKey = $this->siteKey();
        $contentKey ??= 'url:' . hash('sha256', $url);
        $cacheKey = $this->countsCacheKey($siteKey, $contentKey);
        $stored = null;

        if ($settings['cache_counts']) {
            $cached = $this->cache->get($cacheKey);

            if (is_array($cached)) {
                $stored = $cached;
            }
        }

        if ($stored === null) {
            $stored = $this->repository->counts($siteKey, $contentKey);

            if ($settings['cache_counts']) {
                $this->cache->set($cacheKey, $stored, (int) $settings['cache_ttl']);
            }
        }

        $counts = [];
        foreach ($networks as $network) {
            $counts[$network] = max(0, (int) ($stored[$network] ?? 0));
        }

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * @param string $canonicalUrl
     * @param string $contentKey
     * @param string $network
     * @param string $fingerprint
     * @param list<string> $networks
     * @return array{recorded:bool,reason:string,counts:array<string,int>}
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function record(
        string $canonicalUrl,
        string $contentKey,
        string $network,
        string $fingerprint,
        array $networks
    ): array {
        $settings = $this->settings->all();

        if (!$settings['enabled'] || !$settings['track_shares']) {
            return [
                'recorded' => false,
                'reason' => 'disabled',
                'counts' => $this->getCounts($canonicalUrl, $networks, $contentKey, true),
            ];
        }

        if (!in_array($network, $networks, true) || !array_key_exists($network, SocialNetwork::all())) {
            return [
                'recorded' => false,
                'reason' => 'invalid_network',
                'counts' => $this->getCounts($canonicalUrl, $networks, $contentKey, true),
            ];
        }

        $siteKey = $this->siteKey();
        $rateKey = 'rate_' . hash('sha256', $siteKey . '|' . $fingerprint . '|' . gmdate('YmdHi'));
        $attempts = max(0, (int) $this->cache->get($rateKey, 0)) + 1;
        $this->cache->set($rateKey, $attempts, 90);

        if ($attempts > 30) {
            return [
                'recorded' => false,
                'reason' => 'rate_limited',
                'counts' => $this->getCounts($canonicalUrl, $networks, $contentKey, true),
            ];
        }

        $dedupeKey = 'dedupe_' . hash('sha256', $siteKey . '|' . $contentKey . '|' . $network . '|' . $fingerprint);

        if ($this->cache->get($dedupeKey) === true) {
            return [
                'recorded' => false,
                'reason' => 'duplicate',
                'counts' => $this->getCounts($canonicalUrl, $networks, $contentKey, true),
            ];
        }

        $this->repository->increment($siteKey, $contentKey, $canonicalUrl, $network);
        $this->cache->set($dedupeKey, true, 10);
        $this->cache->delete($this->countsCacheKey($siteKey, $contentKey));

        return [
            'recorded' => true,
            'reason' => 'recorded',
            'counts' => $this->getCounts($canonicalUrl, $networks, $contentKey, true),
        ];
    }


    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function total(): int
    {
        return $this->repository->total($this->siteKey());
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function reset(): void
    {
        $this->repository->reset($this->siteKey());
        $this->cache->clear();
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function siteKey(): string
    {
        return trim((string) get_current_site_key());
    }

    private function countsCacheKey(string $siteKey, string $contentKey): string
    {
        return 'counts_' . hash('sha256', $siteKey . '|' . $contentKey);
    }
}
