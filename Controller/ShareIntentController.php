<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Controller;

use Plugin\SocialShare\Security\ShareIntentToken;
use Plugin\SocialShare\Support\ShareCountService;
use Plugin\SocialShare\Support\ShareTarget;
use Plugin\SocialShare\Support\SocialShareSettings;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\ServerRequest;
use ReflectionException;

use function App\Shared\Helpers\get_current_site_key;

final readonly class ShareIntentController
{
    public function __construct(
        private ShareIntentToken $tokens,
        private ShareTarget $targets,
        private ShareCountService $counts,
        private SocialShareSettings $settings,
    ) {
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
     * @throws \Exception
     */
    public function record(ServerRequest $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $network = trim((string) ($body['network'] ?? ''));
        $payload = $this->tokens->verify(trim((string) ($body['share_token'] ?? '')));

        if ($payload === null || $payload['site'] !== trim((string) get_current_site_key())) {
            return JsonResponseFactory::create(['error' => 'Invalid share token.'], 422);
        }

        $canonicalUrl = $this->targets->canonicalUrl($payload['url']);

        if (
            $canonicalUrl === ''
            || !$this->targets->isLocal($canonicalUrl)
            || $this->targets->contentKey($canonicalUrl, $payload['key']) !== $payload['key']
        ) {
            return JsonResponseFactory::create(['error' => 'Invalid share target.'], 422);
        }

        $settings = $this->settings->all();
        $enabledNetworks = array_values(array_intersect(
            array_keys(\Plugin\SocialShare\Support\SocialNetwork::all()),
            $settings['networks']
        ));
        $fingerprint = hash('sha256', implode('|', [
            (string) ($request->getServerParams()['REMOTE_ADDR'] ?? ''),
            $request->getHeaderLine('User-Agent'),
            (string) ($body['_token'] ?? ''),
        ]));
        $result = $this->counts->record(
            $canonicalUrl,
            $payload['key'],
            $network,
            $fingerprint,
            $enabledNetworks
        );
        $status = $result['reason'] === 'rate_limited' ? 429 : 200;

        return JsonResponseFactory::create($result, $status, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @throws Exception
     */
    private function isSameOrigin(ServerRequest $request): bool
    {
        $source = $request->getHeaderLine('Origin') ?: $request->getHeaderLine('Referer');

        if ($source === '') {
            return false;
        }

        $sourceUrl = $this->targets->canonicalUrl($source);

        return $sourceUrl !== '' && $this->targets->isLocal($sourceUrl);
    }
}
