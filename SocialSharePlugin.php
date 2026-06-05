<?php

declare(strict_types=1);

namespace Plugin\SocialShare;

use App\Application\Devflow;
use App\Infrastructure\Services\Plugin;
use App\Shared\Services\Registry;
use App\Shared\Services\Shortcode\ShortcodeRegistry;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use App\Shared\Services\Utils;
use Plugin\SocialShare\Controller\SocialShareController;
use Plugin\SocialShare\Shortcode\SocialShareShortcode;
use Plugin\SocialShare\Support\ShareCountService;
use Plugin\SocialShare\Support\SocialShareRenderer;
use Plugin\SocialShare\Support\SocialShareSettings;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\ServerRequest;
use ReflectionException;

use function App\Shared\Helpers\add_plugins_submenu;
use function App\Shared\Helpers\cms_enqueue_css;
use function App\Shared\Helpers\cms_enqueue_js;
use function App\Shared\Helpers\delete_option;
use function App\Shared\Helpers\get_content_by;
use function App\Shared\Helpers\get_product_by_id;
use function App\Shared\Helpers\plugin_basename;
use function App\Shared\Helpers\plugin_dir_path;
use function App\Shared\Helpers\plugin_url;
use function App\Shared\Helpers\site_url;
use function App\Shared\Helpers\update_option;
use function dirname;
use function get_class;
use function in_array;
use function Qubus\Security\Helpers\esc_html__;

class SocialSharePlugin extends Plugin
{
    /**
     * @inheritDoc
     * @return array
     * @throws Exception
     * @throws ReflectionException
     */
    public function meta(): array
    {
        $plugin = [
            'name' => esc_html__(string: 'Social Share', domain: 'social-share'),
            'id' => 'social-share',
            'slug' => 'SocialShare',
            'author' => 'Joshua Parker',
            'version' => '1.0.0',
            'description' => 'A simple social share plugin that allows your visitors to share and bookmark your content.',
            'basename' => plugin_basename(dirname(__FILE__)),
            'path' => plugin_dir_path(dirname(__FILE__)),
            'url' => plugin_url('', __CLASS__),
            'pluginUri' => 'https://github.com/getdevflow/social-share',
            'authorUri' => 'https://joshuaparker.dev/',
            'className' => get_class($this),
            'screenshot' => plugin_url('SocialShare/images/screenshot.png'),
        ];

        Registry::getInstance()->set('social-share', $plugin);

        return $plugin;
    }

    /**
     * @inheritDoc
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TypeException
     */
    public function handle(): void
    {
        $this->registerAdminAssets();
        $this->registerFrontendAssets();
        $this->registerShortcode();
        $this->registerContentPlacement();
        $this->registerAdminMenu();
        Action::getInstance()->addAction(hook: 'plugins_loaded', callback: [$this, 'render'], priority: 1);
        Action::getInstance()->addAction(hook: 'flush_cache', callback: function () {
            SimpleCacheObjectCacheFactory::make('socialshare')->clear();
        }, priority: 1);
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    private function registerAdminAssets(): void
    {
        if (
                !str_starts_with(
                    Utils::getPathInfo(
                        '/admin/plugin/' . $this->id() . '/'
                    ),
                    '/admin/plugin/' . $this->id() . '/'
                )
        ) {
            return;
        }

        Action::getInstance()->addAction('cms_admin_head', function (): void {
            cms_enqueue_css(
                config: 'plugin',
                asset: $this->url() . '/css/social-share.css',
                slug: $this->id()
            );
        });
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    private function registerFrontendAssets(): void
    {
        Action::getInstance()->addAction('cms_head', function (): void {
            cms_enqueue_css(
                config: 'plugin',
                asset: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css',
                slug: $this->id()
            );
            cms_enqueue_css(
                config: 'plugin',
                asset: $this->url() . '/css/social-share.css',
                slug: $this->id()
            );
        });

        Action::getInstance()->addAction('cms_footer', function (): void {
            cms_enqueue_js(
                config: 'plugin',
                asset: $this->url() . '/js/social-share.js',
                slug: $this->id()
            );
        });
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     */
    private function registerShortcode(): void
    {
        $registry = new ShortcodeRegistry();
        $settings = new SocialShareSettings();
        $renderer = new SocialShareRenderer(
            settings: $settings,
            counter: new ShareCountService($settings)
        );

        $registry->register(new SocialShareShortcode($renderer));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function registerContentPlacement(): void
    {
        Filter::getInstance()->addFilter('the.body', function (string $content, string $id): string {
            $settings = new SocialShareSettings();

            $context = get_content_by('id', $id)->toArray();

            if (!$settings->enabled() || !$settings->shouldDisplayFor($context)) {
                return $content;
            }

            $placement = $settings->placement();

            if ($placement === 'shortcode' || $placement === 'template_tag') {
                return $content;
            }

            $renderer = new SocialShareRenderer(
                settings: $settings,
                counter: new ShareCountService($settings)
            );

            $buttons = $renderer->render(
                $this->shareArgsFromContext($context, 'standard')
            );

            $floating = $settings->all()['floating_enabled']
                ? $renderer->render($this->shareArgsFromContext($context, 'floating'))
                : '';

            $position = $settings->all()['floating_position'] ?? 'vertical_left';
            $isVerticalFloating = in_array($position, ['vertical_left', 'vertical_right'], true);

            if ($isVerticalFloating && $floating !== '') {
                return match ($placement) {
                    'before' => $this->wrapVerticalFloating($position, $floating, $buttons . $content),
                    'after' => $this->wrapVerticalFloating($position, $floating, $content . $buttons),
                    'before_after' => $this->wrapVerticalFloating($position, $floating, $buttons . $content . $buttons),
                    default => $this->wrapVerticalFloating($position, $floating, $content),
                };
            }

            return match ($placement) {
                'before' => $buttons . $content . $floating,
                'after' => $content . $buttons . $floating,
                'before_after' => $buttons . $content . $buttons . $floating,
                default => $content . $floating,
            };
        }, 20, 2);

        Filter::getInstance()->addFilter(
            'get.product.body',
            function (string $content, string $id): string {
                $context = get_product_by_id($id)->toArray();

                $settings = new SocialShareSettings();

                if (!$settings->enabled() || !$settings->shouldDisplayFor($context)) {
                    return $content;
                }

                    $placement = $settings->placement();

                if ($placement === 'shortcode' || $placement === 'template_tag') {
                    return $content;
                }

                    $renderer = new SocialShareRenderer(
                        settings: $settings,
                        counter: new ShareCountService($settings)
                    );

                $buttons = $renderer->render(
                    $this->shareArgsFromContext($context, 'standard')
                );

                $floating = $settings->all()['floating_enabled']
                    ? $renderer->render($this->shareArgsFromContext($context, 'floating'))
                    : '';

                $position = $settings->all()['floating_position'] ?? 'vertical_left';
                $isVerticalFloating = in_array($position, ['vertical_left', 'vertical_right'], true);

                if ($isVerticalFloating && $floating !== '') {
                    return match ($placement) {
                        'before' => $this->wrapVerticalFloating($position, $floating, $buttons . $content),
                        'after' => $this->wrapVerticalFloating($position, $floating, $content . $buttons),
                        'before_after' => $this->wrapVerticalFloating(
                            $position,
                            $floating, $buttons . $content . $buttons
                        ),
                        default => $this->wrapVerticalFloating($position, $floating, $content),
                    };
                }

                return match ($placement) {
                    'before' => $buttons . $content . $floating,
                    'after' => $content . $buttons . $floating,
                    'before_after' => $buttons . $content . $buttons . $floating,
                    default => $content . $floating,
                };
            },
            20,
            2
        );
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    private function registerAdminMenu(): void
    {
        Action::getInstance()->addAction(hook: 'plugins_submenu', callback: function () {
            echo add_plugins_submenu(
                menuTitle: $this->meta()['name'],
                menuRoute: 'plugin/' . $this->meta()['id'],
                screen: $this->meta()['id'],
                permission: 'manage:plugins'
            );
        });
    }

    /**
     * @param array $context
     * @param string $placement
     * @return string[]
     * @throws Exception
     */
    private function shareArgsFromContext(array $context, string $placement): array
    {
        $args = ['placement' => $placement];

        $url = $context['relativeUrl']
            ?? $context['permalink']
            ?? $context['canonical_url']
            ?? $context['link']
            ?? $_SERVER['REQUEST_URI']
            ?? '';

        if ($url !== '' && !str_starts_with($url, 'http')) {
            $url = site_url($url);
        }

        $title = $context['title']
            ?? $context['content_title']
            ?? $context['product_title']
            ?? '';

        $args['url'] = trim((string) $url);
        $args['title'] = trim((string) $title);

        return $args;
    }

    private function wrapVerticalFloating(string $position, string $floating, string $content): string
    {
        $class = $position === 'vertical_right'
            ? 'df-social-share-wrap df-social-share-wrap--right'
            : 'df-social-share-wrap df-social-share-wrap--left';

        if ($position === 'vertical_right') {
            return '<div class="' . $class . '">'
                . '<div class="df-social-share-content">' . $content . '</div>'
                . $floating
                . '</div>';
        }

        return '<div class="' . $class . '">'
            . $floating
            . '<div class="df-social-share-content">' . $content . '</div>'
            . '</div>';
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws TypeException
     */
    public function onActivation(): void
    {
        update_option(SocialShareSettings::OPTION_KEY, new SocialShareSettings()->defaults());
    }

    /**
     * @return void
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws \Exception
     */
    public function onDeactivation(): void
    {
        $settings = new SocialShareSettings();

        if ((bool) $settings->all()['remove_settings'] === false) {
            return;
        }

        delete_option(key: $settings::OPTION_KEY);
    }

    /**
     * @return void
     * @throws \Qubus\Routing\Exceptions\TooLateToAddNewRouteException
     */
    public function render(): void
    {
        $router = Devflow::$PHP->router;

        $router->map(
            verbs: ['GET', 'POST'],
            uri: '/admin/plugin/social-share/',
            callback: fn(ServerRequest $request, SocialShareController $controller) => $controller->settings($request)
        );
    }
}
