<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Shortcode;

use App\Shared\Services\Shortcode\BaseShortcode;
use Plugin\SocialShare\Support\SocialShareRenderer;

final class SocialShareShortcode extends BaseShortcode
{
    protected array $defaultAttributes = [
        'placement' => 'standard',
    ];

    public function __construct(private readonly SocialShareRenderer $renderer)
    {
    }

    public function tag(): string
    {
        return 'social_share';
    }

    /**
     * @param array $attrs
     * @param string|null $content
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    protected function handle(array $attrs, ?string $content = null): string
    {
        return $this->renderer->render($attrs);
    }

    public function isSafe(): bool
    {
        return true;
    }
}
