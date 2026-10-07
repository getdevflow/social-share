<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Controller;

use App\Application\Devflow;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Exception;
use Plugin\SocialShare\Support\ShareCountService;
use Plugin\SocialShare\Support\SocialShareSettings;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\ServerRequest;
use ReflectionException;

use function App\Shared\Helpers\get_all_content_types;
use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\current_user_can;
use function Codefy\Framework\Helpers\view;
use function Qubus\Routing\Helpers\redirect;
use function Qubus\Security\Helpers\t__;

final readonly class SocialShareController
{
    public function __construct(
        private ShareCountService $counts,
    ) {
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws UnresolvableQueryHandlerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     * @throws ReflectionException
     * @throws Exception
     */
    public function settings(ServerRequest $request): ResponseInterface
    {
        if (!current_user_can('manage:plugins')) {
            Devflow::$PHP->flash->error(t__('Access denied.', 'social-share'));

            return redirect(admin_url());
        }

        $settings = new SocialShareSettings();

        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            $body = is_array($body) ? $body : [];
            $input = $body['social_share'] ?? [];

            if ($settings->save(is_array($input) ? $input : []) === true) {
                $this->counts->clearCache();
                Devflow::$PHP->flash->success(
                    Devflow::$PHP->flash->notice(num: 200)
                );
            } else {
                Devflow::$PHP->flash->error('An error occurred.');
            }

            return redirect(admin_url('plugin/social-share/'));
        }

        return view('plugin::SocialShare/view/settings', [
            'settings' => $settings->all(),
            'networks' => \Plugin\SocialShare\Support\SocialNetwork::all(),
            'contentTypes' => get_all_content_types(),
            'shareTotal' => $this->counts->total(),
        ]);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function resetCounts(ServerRequest $request): ResponseInterface
    {
        if (!current_user_can('manage:plugins')) {
            Devflow::$PHP->flash->error(t__('Access denied.', 'social-share'));

            return redirect(admin_url());
        }

        $this->counts->reset();
        Devflow::$PHP->flash->success(t__('Share counts reset successfully.', 'social-share'));

        return redirect(admin_url('plugin/social-share/'));
    }
}
