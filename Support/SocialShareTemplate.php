<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

final class SocialShareTemplate
{
    /**
     * @param array $args
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
        $settings = new \Plugin\SocialShare\Support\SocialShareSettings();
        $renderer = new \Plugin\SocialShare\Support\SocialShareRenderer(
            settings: $settings,
            counter: new \Plugin\SocialShare\Support\ShareCountService($settings)
        );

        return $renderer->render($args);
    }
}
