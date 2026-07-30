<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UiRouteIntegrityTest extends TestCase
{
    public function test_all_named_routes_used_by_the_layout_exist(): void
    {
        $routeNames = collect(config('navigation'))
            ->flatMap(fn (array $group) => $group['items'])
            ->pluck('route')
            ->merge(['password.change', 'logout']);

        $routeNames->each(fn (string $name) => $this->assertTrue(Route::has($name), "Route {$name} tidak tersedia."));
    }
}
