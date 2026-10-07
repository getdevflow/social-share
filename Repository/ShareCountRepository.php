<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Repository;

use DateTimeImmutable;
use Exception;
use PDO;
use PDOStatement;
use Qubus\Expressive\Database;
use Throwable;

final readonly class ShareCountRepository
{
    public function __construct(private Database $dfdb)
    {
    }

    /** @return array<string, int> */
    public function counts(string $siteKey, string $contentKey): array
    {
        $rows = $this->dfdb->getResults(
            $this->dfdb->prepare(
                "SELECT network, share_count FROM {$this->table()}
                 WHERE site_key = ? AND content_key = ? ORDER BY network ASC",
                [$siteKey, $contentKey]
            ),
            Database::ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        $counts = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['network'])) {
                continue;
            }

            $counts[(string) $row['network']] = max(0, (int) ($row['share_count'] ?? 0));
        }

        return $counts;
    }

    public function increment(string $siteKey, string $contentKey, string $canonicalUrl, string $network): int
    {
        $now = new DateTimeImmutable()->format('Y-m-d H:i:s');
        $driver = (string) $this->dfdb->getConnection()->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $params = [$siteKey, $contentKey, $canonicalUrl, $network, $now, $now];

        if ($driver === 'sqlite' || $driver === 'pgsql') {
            $this->dfdb->query(
                "INSERT INTO {$this->table()}
                 (site_key, content_key, canonical_url, network, share_count, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 1, ?, ?)
                 ON CONFLICT(site_key, content_key, network) DO UPDATE SET
                     share_count = share_count + 1,
                     canonical_url = excluded.canonical_url,
                     updated_at = excluded.updated_at",
                $params
            );
        } elseif ($driver === 'mysql') {
            $this->dfdb->query(
                "INSERT INTO {$this->table()}
                 (site_key, content_key, canonical_url, network, share_count, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     share_count = share_count + 1,
                     canonical_url = VALUES(canonical_url),
                     updated_at = VALUES(updated_at)",
                $params
            );
        } else {
            $this->incrementFallback($siteKey, $contentKey, $canonicalUrl, $network, $now);
        }

        return (int) $this->dfdb->getVar(
            $this->dfdb->prepare(
                "SELECT share_count FROM {$this->table()}
                 WHERE site_key = ? AND content_key = ? AND network = ?",
                [$siteKey, $contentKey, $network]
            )
        );
    }

    public function total(string $siteKey): int
    {
        return (int) $this->dfdb->getVar(
            $this->dfdb->prepare(
                "SELECT COALESCE(SUM(share_count), 0) FROM {$this->table()} WHERE site_key = ?",
                [$siteKey]
            )
        );
    }

    public function reset(string $siteKey): void
    {
        $this->dfdb->query(
            $this->dfdb->prepare("DELETE FROM {$this->table()} WHERE site_key = ?", [$siteKey])
        );
    }

    /**
     * @throws Exception
     */
    private function incrementFallback(
        string $siteKey,
        string $contentKey,
        string $canonicalUrl,
        string $network,
        string $now
    ): void {
        $this->dfdb->transactional(function (Database $database) use (
            $siteKey,
            $contentKey,
            $canonicalUrl,
            $network,
            $now
        ): void {
            $statement = $database->query(
                "UPDATE {$this->table()} SET share_count = share_count + 1, canonical_url = ?, updated_at = ?
                 WHERE site_key = ? AND content_key = ? AND network = ?",
                [$canonicalUrl, $now, $siteKey, $contentKey, $network],
                true
            );

            if ($statement instanceof PDOStatement && $statement->rowCount() > 0) {
                return;
            }

            try {
                $database->query(
                    "INSERT INTO {$this->table()}
                     (site_key, content_key, canonical_url, network, share_count, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 1, ?, ?)",
                    [$siteKey, $contentKey, $canonicalUrl, $network, $now, $now]
                );
            } catch (Throwable) {
                $database->query(
                    "UPDATE {$this->table()} SET share_count = share_count + 1, canonical_url = ?, updated_at = ?
                     WHERE site_key = ? AND content_key = ? AND network = ?",
                    [$canonicalUrl, $now, $siteKey, $contentKey, $network]
                );
            }
        });
    }

    private function table(): string
    {
        return $this->dfdb->prefix . 'social_share_count';
    }
}
