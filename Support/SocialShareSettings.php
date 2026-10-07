<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

use function App\Shared\Helpers\get_option;
use function App\Shared\Helpers\update_option;

final class SocialShareSettings
{
    public const string OPTION_KEY = 'social_share_settings';

    /**
     * @return array<string, mixed>
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     * @throws \ReflectionException
     */
    public function all(): array
    {
        $stored = get_option(self::OPTION_KEY, []);

        return $this->normalizeStored(is_array($stored) ? $stored : []);
    }

    /**
     * @param array<string, mixed> $input
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

        $settings['icon_size'] = min(96, max(16, (int) ($input['icon_size'] ?? 38)));

        $settings['track_shares'] = !empty($input['track_shares']);
        $settings['show_counts'] = !empty($input['show_counts']);
        $settings['show_total'] = !empty($input['show_total']);
        $settings['cache_counts'] = !empty($input['cache_counts']);
        $settings['cache_ttl'] = min(604800, max(300, (int) ($input['cache_ttl'] ?? 21600)));
        $settings['remove_data'] = !empty($input['remove_data']);

        $enabled = $input['networks'] ?? [];
        $settings['networks'] = array_values(array_intersect(
            array_keys(SocialNetwork::all()),
            is_array($enabled) ? $enabled : []
        ));

        $settings['display_scope'] = $this->choice($input['display_scope'] ?? 'all_content_and_products', [
            'products',
            'all_content',
            'selected_content_types',
            'all_content_and_products',
        ]);

        $contentTypes = $input['content_types'] ?? [];

        $settings['content_types'] = $this->contentTypes(is_array($contentTypes) ? $contentTypes : []);

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

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'enabled' => false,
            'remove_settings' => false,
            'remove_data' => false,
            'placement' => 'after',
            'floating_enabled' => false,
            'floating_position' => 'vertical_left',
            'floating_mobile' => false,
            'icon_shape' => 'round',
            'icon_size' => 38,
            'track_shares' => true,
            'show_counts' => false,
            'show_total' => false,
            'cache_counts' => true,
            'cache_ttl' => 21600,
            'display_scope' => 'all_content_and_products',
            'content_types' => [],
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

    /** @param list<string> $allowed */
    private function choice(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    /**
     * @param array<string, mixed> $stored
     * @return array<string, mixed>
     */
    private function normalizeStored(array $stored): array
    {
        $settings = array_replace($this->defaults(), $stored);
        $settings['enabled'] = (bool) $settings['enabled'];
        $settings['remove_settings'] = (bool) $settings['remove_settings'];
        $settings['remove_data'] = (bool) $settings['remove_data'];
        $settings['floating_enabled'] = (bool) $settings['floating_enabled'];
        $settings['floating_mobile'] = (bool) $settings['floating_mobile'];
        $settings['track_shares'] = (bool) $settings['track_shares'];
        $settings['show_counts'] = (bool) $settings['show_counts'];
        $settings['show_total'] = (bool) $settings['show_total'];
        $settings['cache_counts'] = (bool) $settings['cache_counts'];
        $settings['placement'] = $this->choice((string) $settings['placement'], [
            'before',
            'after',
            'before_after',
            'shortcode',
            'template_tag',
            'floating',
        ]);
        $settings['floating_position'] = $this->choice((string) $settings['floating_position'], [
            'vertical_left',
            'vertical_right',
            'horizontal_bottom',
            'horizontal_top',
        ]);
        $settings['icon_shape'] = $this->choice((string) $settings['icon_shape'], [
            'square',
            'round',
            'rectangular',
        ]);
        $settings['display_scope'] = $this->choice((string) $settings['display_scope'], [
            'products',
            'all_content',
            'selected_content_types',
            'all_content_and_products',
        ]);
        $settings['icon_size'] = min(96, max(16, (int) $settings['icon_size']));
        $settings['cache_ttl'] = min(604800, max(300, (int) $settings['cache_ttl']));
        $networks = is_array($settings['networks']) ? $settings['networks'] : [];
        $settings['networks'] = array_values(array_intersect(array_keys(SocialNetwork::all()), $networks));
        $contentTypes = is_array($settings['content_types']) ? $settings['content_types'] : [];
        $settings['content_types'] = $this->contentTypes($contentTypes);

        return $settings;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<string>
     */
    private function contentTypes(array $values): array
    {
        $types = [];

        foreach ($values as $value) {
            $value = strtolower(trim((string) $value));

            if ($value !== '' && preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/D', $value)) {
                $types[] = $value;
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * @param array<string, mixed> $context
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
