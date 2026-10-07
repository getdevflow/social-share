<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Shortcode;

use App\Shared\Services\Shortcode\BaseShortcode;
use JsonException;
use Plugin\SocialShare\Support\SocialShareRenderer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;

final class SocialShareShortcode extends BaseShortcode
{
    /** @var array<string, mixed> */
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
     * @param array<string, mixed> $attrs
     * @param string|null $content
     * @return string
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
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
