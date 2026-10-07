<?php

declare(strict_types=1);

namespace Plugin\SocialShare\Tests;

use Codefy\Framework\Http\Middleware\Csrf\CsrfTokenMiddleware;
use PHPUnit\Framework\TestCase;
use Plugin\SocialShare\Security\CsrfGuard;
use Qubus\Http\ServerRequest;

final class CsrfGuardTest extends TestCase
{
    public function testAcceptsMatchingSubmittedToken(): void
    {
        $request = new ServerRequest(parsedBody: ['_token' => 'expected-token'])
            ->withAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE, 'expected-token');

        self::assertTrue(new CsrfGuard()->isValid($request));
    }

    public function testRejectsMissingOrMismatchedSubmittedToken(): void
    {
        $guard = new CsrfGuard();
        $request = new ServerRequest(parsedBody: ['_token' => 'wrong-token'])
            ->withAttribute(CsrfTokenMiddleware::CSRF_SESSION_ATTRIBUTE, 'expected-token');

        self::assertFalse($guard->isValid($request));
        self::assertFalse($guard->isValid(new ServerRequest(parsedBody: [])));
    }
}
