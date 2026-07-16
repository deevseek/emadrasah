<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

final class CrudRouteIntegrityTest extends TestCase
{
    public function test_registered_controller_routes_point_to_existing_methods(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);

            $this->assertTrue(class_exists($controller), "Controller {$controller} tidak ditemukan untuk route {$route->uri()}.");
            $this->assertTrue(method_exists($controller, $method), "Method {$controller}@{$method} tidak ditemukan untuk route {$route->uri()}.");
            $this->assertTrue((new ReflectionMethod($controller, $method))->isPublic(), "Method {$controller}@{$method} harus public.");
        }
    }

    public function test_crud_named_routes_can_be_generated_without_missing_parameters(): void
    {
        $expectedRoutes = [
            'btaq-levels.index',
            'btaq-levels.create',
            'btaq-materials.index',
            'btaq-materials.create',
            'btaq-groups.index',
            'btaq-groups.create',
            'btaq-journals.index',
            'btaq-journals.create',
            'assessment-components.index',
            'assessment-components.create',
            'finance.fee-types.index',
            'finance.fee-types.create',
        ];

        foreach ($expectedRoutes as $routeName) {
            $this->assertTrue(Route::has($routeName), "Route {$routeName} tidak terdaftar.");
            $this->assertIsString(route($routeName));
        }
    }

    public function test_resource_routes_do_not_register_missing_destroy_actions(): void
    {
        $removedDestroyRoutes = [
            'btaq-levels.destroy',
            'btaq-materials.destroy',
            'btaq-groups.destroy',
            'btaq-journals.destroy',
            'assessment-components.destroy',
        ];

        foreach ($removedDestroyRoutes as $routeName) {
            $this->assertFalse(Route::has($routeName), "Route {$routeName} tidak boleh terdaftar tanpa method controller.");
        }
    }
}
