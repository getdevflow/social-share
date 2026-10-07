<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use JsonException;
use Plugin\SocialShare\Security\ShareIntentToken;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\get_current_site_key;
use function App\Shared\Helpers\site_url;
use function Qubus\Security\Helpers\esc_attr;
use function Qubus\Security\Helpers\esc_html;

final readonly class SocialShareRenderer
{
    public function __construct(
        private SocialShareSettings $settings,
        private ShareCountService $counter,
        private ShareTarget $targets,
        private ShareIntentToken $tokens,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return string
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
     */
    public function render(array $args = []): string
    {
        $settings = $this->settings->all();

        if (!$settings['enabled']) {
            return '';
        }

        $url = trim((string) ($args['url'] ?? ($_SERVER['REQUEST_URI'] ?? '')));
        $title = mb_substr(trim((string) ($args['title'] ?? '')), 0, 300);
        $url = $this->targets->canonicalUrl($url);

        if ($url === '') {
            return '';
        }

        $networks = SocialNetwork::all();
        $enabledKeys = is_array($settings['networks']) ? $settings['networks'] : [];
        $enabled = array_intersect_key($networks, array_flip($enabledKeys));

        if ($enabled === []) {
            return '';
        }

        $contentKey = $this->targets->contentKey(
            $url,
            isset($args['content_key']) ? (string) $args['content_key'] : null
        );
        $counts = $this->counter->getCounts($url, array_keys($enabled), $contentKey);
        $trackable = !empty($settings['track_shares']) && $this->targets->isLocal($url);
        $shareToken = $trackable
        ? $this->tokens->issue(trim((string) get_current_site_key()), $contentKey, $url)
        : '';

        $placement = in_array(($args['placement'] ?? ''), ['standard', 'floating'], true)
        ? (string) $args['placement']
        : 'standard';
        $classes = [
            'df-social-share',
            'df-social-share--' . $settings['icon_shape'],
            'df-social-share--' . $placement,
        ];

        if ($placement === 'floating') {
            $classes[] = 'df-social-share--floating';
            $classes[] = 'df-social-share--' . $settings['floating_position'];

            if (empty($settings['floating_mobile'])) {
                $classes[] = 'df-social-share--hide-mobile';
            }
        }

        $size = max(16, (int) $settings['icon_size']);

        $html = '<nav class="' . esc_attr(implode(' ', $classes)) . '" aria-label="Share this content"';

        if ($shareToken !== '') {
            $html .= ' data-share-endpoint="' . esc_attr(site_url('/social-share/record/')) . '"';
            $html .= ' data-share-token="' . esc_attr($shareToken) . '"';
        }

        $html .= '>';

        if ($shareToken !== '') {
            $html .= csrf_field();
        }

        $html .= '<ul class="df-social-share__list" style="--df-share-size:' . esc_attr((string) $size) . 'px">';

        if (!empty($settings['show_total'])) {
            $html .= '<li class="df-social-share__total" aria-label="Total shares">';
            $html .= '<strong data-share-count-total>' . esc_html((string) ($counts['total'] ?? 0)) . '</strong>';
            $html .= '<span>Shares</span>';
            $html .= '</li>';
        }

        foreach ($enabled as $key => $network) {
            $shareUrl = $this->buildShareUrl($network['url'], $url, $title);
            $isCopy = $key === 'copy';
            $count = (string) ($counts[$key] ?? 0);

            $html .= '<li class="df-social-share__item">';
            $html .= '<a class="df-social-share__button"';
            $html .= ' href="' . esc_attr($this->sanitizeShareUrl($shareUrl)) . '"';
            $html .= ' style="--df-share-color:' . esc_attr($network['color']) . '"';
            $html .= ' aria-label="Share on ' . esc_attr($network['label']) . '"';

            if ($shareToken !== '') {
                $html .= ' data-share-network="' . esc_attr($key) . '"';
            }

            if ($isCopy) {
                $html .= ' data-share-copy="' . esc_attr($url) . '"';
            } else {
                $html .= ' target="_blank" rel="noopener noreferrer nofollow"';
            }

            $html .= '>';
            $html .= '<i class="' . esc_attr($network['icon']) . '" aria-hidden="true"></i>';
            $html .= '<span class="sr-only">Share on ' . esc_html($network['label']) . '</span>';

            if (!empty($settings['show_counts']) && $settings['icon_shape'] === 'rectangular') {
                $html .= '<span class="df-social-share__count" data-share-count-network="' . esc_attr($key) . '">';
                $html .= esc_html($count);
                $html .= '</span>';
            }

            $html .= '</a>';

            if (
                    !empty($settings['show_counts'])
                    && in_array($settings['icon_shape'], ['square', 'round'], true)
            ) {
                $html .= '<span class="df-social-share__count df-social-share__count--below"';
                $html .= ' data-share-count-network="' . esc_attr($key) . '">';
                $html .= esc_html($count);
                $html .= '</span>';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    private function buildShareUrl(string $template, string $url, string $title): string
    {
        if ($template === '#copy') {
            return '#copy';
        }

        return strtr($template, [
            '{url}' => rawurlencode($url),
            '{title}' => rawurlencode($title),
        ]);
    }

    private function sanitizeShareUrl(string $url): string
    {
        if ($url === '#copy') {
            return '#copy';
        }

        $allowedSchemes = [
            'http',
            'https',
            'mailto',
            'sms',
            'viber',
        ];

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme === null) {
            return '';
        }

        if (!is_string($scheme) || !in_array(strtolower($scheme), $allowedSchemes, true)) {
            return '';
        }

        return $url;
    }
}
