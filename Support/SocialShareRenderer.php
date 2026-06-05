<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use function Qubus\Security\Helpers\esc_attr;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\esc_url;

final readonly class SocialShareRenderer
{
    public function __construct(
        private SocialShareSettings $settings,
        private ShareCountService $counter
    ) {
    }

    /**
     * @param array $args
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function render(array $args = []): string
    {
        $settings = array_replace($this->settings->all(), $args);

        $url = trim((string) ($args['url'] ?? ''));
        $title = trim((string) ($args['title'] ?? ''));

        if ($url === '') {
            return '';
        }

        $networks = SocialNetwork::all();
        $enabled = array_intersect_key($networks, array_flip($settings['networks']));

        if ($enabled === []) {
            return '';
        }

        $counts = $this->counter->getCounts($url, array_keys($enabled));

        $classes = [
            'df-social-share',
            'df-social-share--' . $settings['icon_shape'],
            'df-social-share--' . ($args['placement'] ?? 'standard'),
        ];

        if (($args['placement'] ?? '') === 'floating') {
            $classes[] = 'df-social-share--floating';
            $classes[] = 'df-social-share--' . $settings['floating_position'];

            if (empty($settings['floating_mobile'])) {
                $classes[] = 'df-social-share--hide-mobile';
            }
        }

        $size = max(16, (int) $settings['icon_size']);

        $html = '<nav class="' . esc_attr(implode(' ', $classes)) . '" aria-label="Share this content">';
        $html .= '<ul class="df-social-share__list" style="--df-share-size:' . esc_attr((string) $size) . 'px">';

        if (!empty($settings['show_total'])) {
            $html .= '<li class="df-social-share__total" aria-label="Total shares">';
            $html .= '<strong>' . esc_html((string) ($counts['total'] ?? 0)) . '</strong>';
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

            if ($isCopy) {
                $html .= ' data-share-copy="' . esc_attr($url) . '"';
            } else {
                $html .= ' target="_blank" rel="noopener noreferrer nofollow"';
            }

            $html .= '>';
            $html .= '<i class="' . esc_attr($network['icon']) . '" aria-hidden="true"></i>';
            $html .= '<span class="sr-only">Share on ' . esc_html($network['label']) . '</span>';

            if (!empty($settings['show_counts']) && $settings['icon_shape'] === 'rectangular') {
                $html .= '<span class="df-social-share__count">';
                $html .= esc_html($count);
                $html .= '</span>';
            }

            $html .= '</a>';

            if (
                    !empty($settings['show_counts'])
                    && in_array($settings['icon_shape'], ['square', 'round'], true)
            ) {
                $html .= '<span class="df-social-share__count df-social-share__count--below">';
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

        if (!in_array(strtolower($scheme), $allowedSchemes, true)) {
            return '';
        }

        return $url;
    }
}
