<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use Qubus\Exception\Exception;

use function App\Shared\Helpers\site_url;

final readonly class ShareTarget
{
    public function __construct(private ?string $siteBase = null)
    {
    }

    /**
     * @throws Exception
     */
    public function canonicalUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '//')) {
            $baseScheme = parse_url($this->siteUrl('/'), PHP_URL_SCHEME);
            $url = (is_string($baseScheme) ? $baseScheme : 'https') . ':' . $url;
        } elseif (!preg_match('#^https?://#i', $url)) {
            $suppliedScheme = parse_url($url, PHP_URL_SCHEME);

            if (is_string($suppliedScheme) && $suppliedScheme !== '') {
                return '';
            }

            $url = $this->siteUrl($url);
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return '';
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $portPart = $port !== null && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))
        ? ':' . $port
        : '';
        $path = (string) ($parts['path'] ?? '/');
        $path = $path === '' ? '/' : '/' . ltrim($path, '/');

        $canonicalUrl = $scheme . '://' . $host . $portPart . $path;

        return strlen($canonicalUrl) <= 500 ? $canonicalUrl : '';
    }

    public function contentKey(string $canonicalUrl, ?string $provided = null): string
    {
        $provided = trim((string) $provided);

        if ($provided !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,95}$/D', $provided)) {
            return $provided;
        }

        return 'url:' . hash('sha256', $canonicalUrl);
    }

    /**
     * @throws Exception
     */
    public function isLocal(string $canonicalUrl): bool
    {
        $site = parse_url($this->canonicalUrl($this->siteUrl('/')));
        $target = parse_url($canonicalUrl);

        if (!is_array($site) || !is_array($target)) {
            return false;
        }

        return strtolower((string) ($site['host'] ?? '')) === strtolower((string) ($target['host'] ?? ''))
        && $this->effectivePort($site) === $this->effectivePort($target);
    }

    /**
     * @throws Exception
     */
    private function siteUrl(string $path): string
    {
        if ($this->siteBase === null) {
            return site_url($path);
        }

        return rtrim($this->siteBase, '/') . '/' . ltrim($path, '/');
    }

    /** @param array<string, mixed> $parts */
    private function effectivePort(array $parts): int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https' ? 443 : 80;
    }
}
