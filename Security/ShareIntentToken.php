<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Security;

use JsonException;

use function Codefy\Framework\Helpers\config;

final readonly class ShareIntentToken
{
    public function __construct(private ?string $secret = null)
    {
    }

    /**
     * @throws JsonException
     */
    public function issue(string $siteKey, string $contentKey, string $canonicalUrl): string
    {
        $payload = $this->encode(json_encode([
            'v' => 1,
            'site' => $siteKey,
            'key' => $contentKey,
            'url' => $canonicalUrl,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $payload . '.' . hash_hmac('sha256', $payload, $this->secret());
    }

    /**
     * @return array{site:string,key:string,url:string}|null
     */
    public function verify(string $token): ?array
    {
        if ($token === '' || strlen($token) > 2048) {
            return null;
        }

        $parts = explode('.', $token, 2);

        if (
            count($parts) !== 2
            || preg_match('/^[a-f0-9]{64}$/D', $parts[1]) !== 1
            || !hash_equals(hash_hmac('sha256', $parts[0], $this->secret()), $parts[1])
        ) {
            return null;
        }

        $json = $this->decode($parts[0]);

        if ($json === null) {
            return null;
        }

        try {
            $payload = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (
            !is_array($payload)
            || ($payload['v'] ?? null) !== 1
            || !is_string($payload['site'] ?? null)
            || !is_string($payload['key'] ?? null)
            || !is_string($payload['url'] ?? null)
            || strlen($payload['site']) > 64
            || strlen($payload['key']) > 96
            || strlen($payload['url']) > 500
        ) {
            return null;
        }

        return [
            'site' => $payload['site'],
            'key' => $payload['key'],
            'url' => $payload['url'],
        ];
    }

    private function secret(): string
    {
        return $this->secret ?? config()->string('cms.app_salt');
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode(string $value): ?string
    {
        $padding = strlen($value) % 4;

        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }
}
