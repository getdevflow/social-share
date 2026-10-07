<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Tests;

use PHPUnit\Framework\TestCase;
use Plugin\SocialShare\Support\ShareTarget;

final class ShareTargetTest extends TestCase
{
    public function testCanonicalizesLocalUrlsAndRemovesTrackingVariants(): void
    {
        $targets = new ShareTarget('https://Example.com');

        self::assertSame(
            'https://example.com/articles/example/',
            $targets->canonicalUrl('/articles/example/?utm_source=test#section')
        );
        self::assertTrue($targets->isLocal('https://example.com/articles/example/'));
    }

    public function testRejectsUnsupportedAndExternalTargets(): void
    {
        $targets = new ShareTarget('https://example.com');

        self::assertSame('', $targets->canonicalUrl('ftp://example.com/file'));
        self::assertSame('', $targets->canonicalUrl('https://example.com/' . str_repeat('a', 500)));
        self::assertFalse($targets->isLocal('https://attacker.example/page/'));
        self::assertFalse($targets->isLocal('http://example.com/page/'));
        self::assertFalse($targets->isLocal('https://example.com:8443/page/'));
    }

    public function testUsesValidatedContentKeysAndFallsBackToUrlHash(): void
    {
        $targets = new ShareTarget('https://example.com');
        $url = 'https://example.com/article/';

        self::assertSame('content:abc-123', $targets->contentKey($url, 'content:abc-123'));
        self::assertSame('url:' . hash('sha256', $url), $targets->contentKey($url, '../invalid'));
    }
}
