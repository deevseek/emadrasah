<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\EnsureUserHasPermission;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class AlternativePermissionMiddlewareTest extends TestCase
{
    public function test_it_accepts_one_matching_permission_from_a_pipe_separated_list(): void
    {
        $request = Request::create('/finance/payrolls');
        $request->setUserResolver(static fn (): object => new class
        {
            public function can(string $permission): bool
            {
                return $permission === 'payrolls.view-own';
            }
        });

        $response = app(EnsureUserHasPermission::class)->handle(
            $request,
            static fn () => response('ok'),
            'payrolls.view|payrolls.view-own',
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }

    public function test_it_rejects_when_none_of_the_alternative_permissions_match(): void
    {
        $request = Request::create('/finance/payrolls');
        $request->setUserResolver(static fn (): object => new class
        {
            public function can(string $permission): bool
            {
                return false;
            }
        });

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);

        app(EnsureUserHasPermission::class)->handle(
            $request,
            static fn () => response('ok'),
            'payrolls.view|payrolls.view-own',
        );
    }
}
