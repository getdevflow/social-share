<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Security;

use Codefy\Framework\Http\Middleware\Csrf\CsrfTokenMiddleware;
use Qubus\Http\ServerRequest;

final class CsrfGuard
{
    public function isValid(ServerRequest $request): bool
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $expected = $request->getAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE);
        $provided = $body['_token'] ?? null;

        return is_string($expected)
        && $expected !== ''
        && is_string($provided)
        && hash_equals($expected, $provided);
    }
}
