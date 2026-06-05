<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use function App\Shared\Helpers\get_option;
use function App\Shared\Helpers\update_option;

final class SocialShareSettings
{
    public const string OPTION_KEY = 'social_share_settings';

    /**
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function all(): array
    {
        return array_replace_recursive($this->defaults(), get_option(self::OPTION_KEY, []));
    }

    /**
     * @param array $input
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Data\TypeException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function save(array $input): bool
    {
        $settings = $this->defaults();

        $settings['enabled'] = !empty($input['enabled']);
        $settings['remove_settings'] = !empty($input['remove_settings']);
        $settings['placement'] = $this->choice($input['placement'] ?? 'after', [
            'before',
            'after',
            'before_after',
            'shortcode',
            'template_tag',
            'floating',
        ]);

        $settings['floating_enabled'] = !empty($input['floating_enabled']);
        $settings['floating_position'] = $this->choice($input['floating_position'] ?? 'vertical_left', [
            'vertical_left',
            'vertical_right',
            'horizontal_bottom',
            'horizontal_top',
        ]);

        $settings['floating_mobile'] = !empty($input['floating_mobile']);
        $settings['icon_shape'] = $this->choice($input['icon_shape'] ?? 'round', [
            'square',
            'round',
            'rectangular',
        ]);

        $settings['icon_size'] = max(16, (int) ($input['icon_size'] ?? 38));

        $settings['show_counts'] = !empty($input['show_counts']);
        $settings['show_total'] = !empty($input['show_total']);
        $settings['cache_counts'] = !empty($input['cache_counts']);
        $settings['cache_ttl'] = max(300, (int) ($input['cache_ttl'] ?? 21600));

        $enabled = $input['networks'] ?? [];
        $settings['networks'] = array_values(array_intersect(
            array_keys(SocialNetwork::all()),
            is_array($enabled) ? $enabled : []
        ));

        $settings['display_scope'] = $this->choice($input['display_scope'] ?? 'content_and_products', [
            'products',
            'all_content',
            'selected_content_types',
            'all_content_and_products',
        ]);

        $contentTypes = $input['content_types'] ?? [];

        $settings['content_types'] = is_array($contentTypes)
            ? array_values(array_filter(array_map('\Qubus\Security\Helpers\purify_html', $contentTypes)))
            : [];

        return update_option(self::OPTION_KEY, $settings);
    }

    /**
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function enabled(): bool
    {
        return (bool) $this->all()['enabled'];
    }

    /**
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function placement(): string
    {
        return $this->all()['placement'];
    }

    public function defaults(): array
    {
        return [
            'enabled' => false,
            'remove_settings' => false,
            'placement' => 'after',
            'floating_enabled' => false,
            'floating_position' => 'vertical_left',
            'floating_mobile' => false,
            'icon_shape' => 'round',
            'icon_size' => 38,
            'show_counts' => false,
            'show_total' => false,
            'cache_counts' => true,
            'cache_ttl' => 21600,
            'display_scope' => 'all_content_and_products',
            'networks' => [
                'facebook',
                'x',
                'linkedin',
                'pinterest',
                'reddit',
                'whatsapp',
                'email',
                'copy',
            ],
        ];
    }

    private function choice(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    /**
     * @param array $context
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function shouldDisplayFor(array $context): bool
    {
        $settings = $this->all();

        $scope = (string) ($settings['display_scope'] ?? 'all_content_and_products');

        $type = $context['type']
            ?? $context['object_type']
            ?? $context['entity_type']
            ?? null;

        $contentType = $context['type']
            ?? $context['content_type_slug']
            ?? $context['type_slug']
            ?? null;

        $type = is_string($type) ? strtolower(trim($type)) : null;
        $contentType = is_string($contentType) ? strtolower(trim($contentType)) : null;

        $selectedContentTypes = array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            $settings['content_types'] ?? []
        );

        $selectedContentTypes = array_filter($selectedContentTypes);

        $isProduct = in_array($type, ['product', 'products'], true);

        $isContent = !$isProduct && (
            $type === 'content'
            || $type === 'post'
            || $type === 'page'
            || $contentType !== null
        );

        return match ($scope) {
            'products' => $isProduct,

            'all_content' => $isContent,

            'selected_content_types' => $isContent
                    && $contentType !== null
                    && in_array($contentType, $selectedContentTypes, true),

            'all_content_and_products' => $isContent || $isProduct,

            default => false,
        };
    }
}
