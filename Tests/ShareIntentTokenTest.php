<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Tests;

use PHPUnit\Framework\TestCase;
use Plugin\SocialShare\Security\ShareIntentToken;

final class ShareIntentTokenTest extends TestCase
{
    public function testRoundTripsSignedPayload(): void
    {
        $tokens = new ShareIntentToken('test-secret');
        $token = $tokens->issue('site-a', 'content:123', 'https://example.com/articles/example/');

        self::assertSame([
            'site' => 'site-a',
            'key' => 'content:123',
            'url' => 'https://example.com/articles/example/',
        ], $tokens->verify($token));
    }

    public function testRejectsTamperingAndMalformedTokens(): void
    {
        $tokens = new ShareIntentToken('test-secret');
        $token = $tokens->issue('site-a', 'content:123', 'https://example.com/example/');

        self::assertNull($tokens->verify($token . 'changed'));
        self::assertNull($tokens->verify('not-a-token'));
        self::assertNull($tokens->verify(str_repeat('a', 2049)));
        self::assertNull(new ShareIntentToken('different-secret')->verify($token));
    }
}
