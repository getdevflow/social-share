# Social Share

A simple social share plugin that allows your visitors to share and bookmark your content.

> __Requires__ Devflow Version: 2.2.3

> __Tested Up To:__ 2.2.3

> __Requires PHP:__ 8.4+

> __Stable Tag:__ 1.0.0

> __License:__ GPLv2-only

## Screenshot

![screenshot.png](public/images/screenshot.png)

## Codex Installation

1. Start a new shell session.
2. Navigate to the root of your install, run the following command ```php codex plugin:install getdevflow/social-share```.

## Template/Theme Usage

```php
use Plugin\SocialShare\Support\SocialShareTemplate;

echo SocialShareTemplate::tag([
    'placement' => 'standard',
    'url' => site_url('/custom-url'),
    'title' => 'Custom Share Title',
]);
```
