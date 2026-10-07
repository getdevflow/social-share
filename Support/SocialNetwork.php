<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Support;

final class SocialNetwork
{
    /**
     * @return array<string, array{label:string,icon:string,color:string,url:string}>
     */
    public static function all(): array
    {
        return [
            'facebook' => [
                'label' => 'Facebook',
                'icon' => 'fa-brands fa-facebook-f',
                'color' => '#1877F2',
                'url' => 'https://www.facebook.com/sharer/sharer.php?u={url}',
            ],
            'x' => [
                'label' => 'X',
                'icon' => 'fa-brands fa-x-twitter',
                'color' => '#111111',
                'url' => 'https://x.com/intent/tweet?url={url}&text={title}',
            ],
            'linkedin' => [
                'label' => 'LinkedIn',
                'icon' => 'fa-brands fa-linkedin-in',
                'color' => '#0A66C2',
                'url' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
            ],
            'pinterest' => [
                'label' => 'Pinterest',
                'icon' => 'fa-brands fa-pinterest-p',
                'color' => '#BD081C',
                'url' => 'https://pinterest.com/pin/create/button/?url={url}&description={title}',
            ],
            'reddit' => [
                'label' => 'Reddit',
                'icon' => 'fa-brands fa-reddit-alien',
                'color' => '#FF4500',
                'url' => 'https://www.reddit.com/submit?url={url}&title={title}',
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'icon' => 'fa-brands fa-whatsapp',
                'color' => '#25D366',
                'url' => 'https://api.whatsapp.com/send?text={title}%20{url}',
            ],
            'telegram' => [
                'label' => 'Telegram',
                'icon' => 'fa-brands fa-telegram',
                'color' => '#229ED9',
                'url' => 'https://t.me/share/url?url={url}&text={title}',
            ],
            'threads' => [
                'label' => 'Threads',
                'icon' => 'fa-brands fa-threads',
                'color' => '#111111',
                'url' => 'https://www.threads.net/intent/post?text={title}%20{url}',
            ],
            'tumblr' => [
                'label' => 'Tumblr',
                'icon' => 'fa-brands fa-tumblr',
                'color' => '#35465C',
                'url' => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}&title={title}',
            ],
            'mastodon' => [
                'label' => 'Mastodon',
                'icon' => 'fa-brands fa-mastodon',
                'color' => '#6364FF',
                'url' => 'https://mastodonshare.com/?url={url}&text={title}',
            ],
            'bluesky' => [
                'label' => 'Bluesky',
                'icon' => 'fa-brands fa-bluesky',
                'color' => '#1185FE',
                'url' => 'https://bsky.app/intent/compose?text={title}%20{url}',
            ],
            'email' => [
                'label' => 'Email',
                'icon' => 'fa-solid fa-envelope',
                'color' => '#6B7280',
                'url' => 'mailto:?subject={title}&body={url}',
            ],
            'sms' => [
                'label' => 'SMS',
                'icon' => 'fa-solid fa-comment-sms',
                'color' => '#10B981',
                'url' => 'sms:?&body={title}%20{url}',
            ],
            'messenger' => [
                'label' => 'Messenger',
                'icon' => 'fa-brands fa-facebook-messenger',
                'color' => '#0084FF',
                'url' => 'https://www.facebook.com/dialog/send?link={url}',
            ],
            'viber' => [
                'label' => 'Viber',
                'icon' => 'fa-brands fa-viber',
                'color' => '#7360F2',
                'url' => 'viber://forward?text={title}%20{url}',
            ],
            'vk' => [
                'label' => 'VK',
                'icon' => 'fa-brands fa-vk',
                'color' => '#0077FF',
                'url' => 'https://vk.com/share.php?url={url}&title={title}',
            ],
            'xing' => [
                'label' => 'Xing',
                'icon' => 'fa-brands fa-xing',
                'color' => '#006567',
                'url' => 'https://www.xing.com/spi/shares/new?url={url}',
            ],
            'weibo' => [
                'label' => 'Weibo',
                'icon' => 'fa-brands fa-weibo',
                'color' => '#E6162D',
                'url' => 'https://service.weibo.com/share/share.php?url={url}&title={title}',
            ],
            'line' => [
                'label' => 'LINE',
                'icon' => 'fa-brands fa-line',
                'color' => '#06C755',
                'url' => 'https://social-plugins.line.me/lineit/share?url={url}',
            ],
            'copy' => [
                'label' => 'Copy Link',
                'icon' => 'fa-solid fa-link',
                'color' => '#374151',
                'url' => '#copy',
            ],
        ];
    }
}
