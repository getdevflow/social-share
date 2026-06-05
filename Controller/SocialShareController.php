<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Controller;

use App\Application\Devflow;
use Exception;
use Plugin\SocialShare\Support\SocialShareSettings;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\ServerRequest;

use function App\Shared\Helpers\get_all_content_types;
use function Codefy\Framework\Helpers\view;
use function Qubus\Routing\Helpers\redirect;

final class SocialShareController
{
    /**
     * @throws Exception
     */
    public function settings(ServerRequest $request): ResponseInterface
    {
        $settings = new SocialShareSettings();

        if ($request->getMethod() === 'POST') {
            if ($settings->save($request->getParsedBody()['social_share'] ?? []) === true) {
                Devflow::$PHP->flash->success(
                    Devflow::$PHP->flash->notice(num: 200)
                );
            } else {
                Devflow::$PHP->flash->error('An error occurred.');
            }

            return redirect($request->getHeaderLine('Referer'));
        }

        return view('plugin::SocialShare/view/settings', [
            'settings' => $settings->all(),
            'networks' => \Plugin\SocialShare\Support\SocialNetwork::all(),
            'contentTypes' => get_all_content_types(),
        ]);
    }
}
