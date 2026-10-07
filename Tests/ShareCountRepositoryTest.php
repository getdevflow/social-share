<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Tests;

use App\Shared\Services\Registry;
use PHPUnit\Framework\TestCase;
use Plugin\SocialShare\Repository\ShareCountRepository;
use Plugin\SocialShare\Support\SocialShareSchema;
use Qubus\Expressive\Connection\Pdo\Sqlite;
use Qubus\Expressive\QueryBuilder;

final class ShareCountRepositoryTest extends TestCase
{
    private QueryBuilder $database;
    private ShareCountRepository $repository;

    /** @var array<string, mixed> */
    private array $originalRegistry;

    protected function setUp(): void
    {
        $registry = Registry::getInstance();
        $this->originalRegistry = $registry->getAll();
        $registry->set('tblPrefix', 'test_');
        $connection = new Sqlite([
            'dsn' => 'sqlite::memory:',
            'driver' => 'pdo_sqlite',
        ]);
        $this->database = new QueryBuilder($connection);
        new SocialShareSchema($this->database)->migrateUp();
        $this->repository = new ShareCountRepository($this->database);
    }

    protected function tearDown(): void
    {
        $registry = Registry::getInstance();
        $registry->clear();
        $registry->add($this->originalRegistry);
    }

    public function testAtomicallyIncrementsNetworksAndReturnsCounts(): void
    {
        self::assertSame(1, $this->repository->increment(
            'site-a',
            'content:123',
            'https://example.com/article/',
            'facebook'
        ));
        self::assertSame(2, $this->repository->increment(
            'site-a',
            'content:123',
            'https://example.com/article/',
            'facebook'
        ));
        self::assertSame(1, $this->repository->increment(
            'site-a',
            'content:123',
            'https://example.com/article/',
            'copy'
        ));

        self::assertSame([
            'copy' => 1,
            'facebook' => 2,
        ], $this->repository->counts('site-a', 'content:123'));
        self::assertSame(3, $this->repository->total('site-a'));
    }

    public function testSeparatesSitesAndCanResetOneSite(): void
    {
        $this->repository->increment('site-a', 'content:123', 'https://example.com/article/', 'facebook');
        $this->repository->increment('site-b', 'content:123', 'https://example.net/article/', 'facebook');
        $this->repository->reset('site-a');

        self::assertSame([], $this->repository->counts('site-a', 'content:123'));
        self::assertSame(['facebook' => 1], $this->repository->counts('site-b', 'content:123'));
    }
}
