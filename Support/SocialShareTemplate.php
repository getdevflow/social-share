<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use App\Application\Devflow;

final class SocialShareTemplate
{
    /**
     * @param array<string, mixed> $args
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Data\TypeException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public static function tag(array $args = []): string
    {
        return Devflow::$PHP->make(SocialShareRenderer::class)->render($args);
    }
}
