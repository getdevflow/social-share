<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use App\Shared\Services\Registry;
use Qubus\Expressive\Database;
use Qubus\Expressive\Schema\CreateTable;

final readonly class SocialShareSchema
{
    public const string VERSION = '1.1.0';

    public function __construct(private Database $dfdb)
    {
    }

    /**
     * @throws \Qubus\Exception\Exception
     */
    public function migrateUp(): void
    {
        $tableName = $this->table();

        if ($this->dfdb->schema()->hasTable($tableName)) {
            return;
        }

        $this->dfdb->schema()->create($tableName, function (CreateTable $table): void {
            $table->string('site_key', 64)->notNull();
            $table->string('content_key', 96)->notNull();
            $table->string('canonical_url', 500)->notNull();
            $table->string('network', 24)->notNull();
            $table->integer('share_count')->size('big')->unsigned()->notNull()->defaultValue(0);
            $table->dateTime('created_at')->notNull();
            $table->dateTime('updated_at')->notNull();
            $table->unique(['site_key', 'content_key', 'network']);
        });
    }

    public function migrateDown(): void
    {
        if ($this->dfdb->schema()->hasTable($this->table())) {
            $this->dfdb->schema()->drop($this->table());
        }
    }

    private function table(): string
    {
        return $this->dfdb->prefix . 'social_share_count';
    }
}
