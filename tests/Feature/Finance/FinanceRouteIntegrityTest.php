<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use Illuminate\Routing\Route;
use Tests\TestCase;

final class FinanceRouteIntegrityTest extends TestCase
{
    public function test_finance_crud_and_workflow_routes_are_registered(): void
    {
        $expectedRoutes = [
            'finance.fee-types.index' => ['GET'],
            'finance.fee-types.create' => ['GET'],
            'finance.fee-types.store' => ['POST'],
            'finance.fee-types.show' => ['GET'],
            'finance.fee-types.edit' => ['GET'],
            'finance.fee-types.update' => ['PUT'],
            'finance.fee-types.toggle' => ['PATCH'],
            'finance.fee-types.destroy' => ['DELETE'],
            'finance.student-invoices.index' => ['GET'],
            'finance.student-invoices.create' => ['GET'],
            'finance.student-invoices.store' => ['POST'],
            'finance.student-invoices.show' => ['GET'],
            'finance.student-invoices.edit' => ['GET'],
            'finance.student-invoices.update' => ['PUT'],
            'finance.student-invoices.cancel' => ['PATCH'],
            'finance.student-payments.index' => ['GET'],
            'finance.student-payments.create' => ['GET'],
            'finance.student-payments.store' => ['POST'],
            'finance.student-payments.show' => ['GET'],
            'finance.student-payments.receipt' => ['GET'],
            'finance.student-payments.cancel' => ['PATCH'],
            'finance.billing-periods.index' => ['GET'],
            'finance.billing-periods.create' => ['GET'],
            'finance.billing-periods.store' => ['POST'],
            'finance.billing-periods.show' => ['GET'],
            'finance.billing-periods.edit' => ['GET'],
            'finance.billing-periods.update' => ['PUT'],
            'finance.billing-periods.toggle' => ['PATCH'],
            'finance.billing-periods.destroy' => ['DELETE'],
            'finance.student-discounts.index' => ['GET'],
            'finance.student-discounts.create' => ['GET'],
            'finance.student-discounts.store' => ['POST'],
            'finance.student-discounts.show' => ['GET'],
            'finance.student-discounts.edit' => ['GET'],
            'finance.student-discounts.update' => ['PUT'],
            'finance.student-discounts.approve' => ['PATCH'],
            'finance.student-discounts.reject' => ['PATCH'],
            'finance.student-discounts.destroy' => ['DELETE'],
            'finance.chart-accounts.index' => ['GET'],
            'finance.chart-accounts.create' => ['GET'],
            'finance.chart-accounts.store' => ['POST'],
            'finance.chart-accounts.show' => ['GET'],
            'finance.chart-accounts.edit' => ['GET'],
            'finance.chart-accounts.update' => ['PUT'],
            'finance.chart-accounts.toggle' => ['PATCH'],
            'finance.chart-accounts.destroy' => ['DELETE'],
            'finance.cash-accounts.index' => ['GET'],
            'finance.cash-accounts.create' => ['GET'],
            'finance.cash-accounts.store' => ['POST'],
            'finance.cash-accounts.show' => ['GET'],
            'finance.cash-accounts.edit' => ['GET'],
            'finance.cash-accounts.update' => ['PUT'],
            'finance.cash-accounts.toggle' => ['PATCH'],
            'finance.cash-accounts.destroy' => ['DELETE'],
            'finance.transactions.index' => ['GET'],
            'finance.transactions.create' => ['GET'],
            'finance.transactions.store' => ['POST'],
            'finance.transactions.show' => ['GET'],
            'finance.transactions.cancel' => ['PATCH'],
            'finance.salary-components.index' => ['GET'],
            'finance.salary-components.create' => ['GET'],
            'finance.salary-components.store' => ['POST'],
            'finance.salary-components.show' => ['GET'],
            'finance.salary-components.edit' => ['GET'],
            'finance.salary-components.update' => ['PUT'],
            'finance.salary-components.toggle' => ['PATCH'],
            'finance.salary-components.destroy' => ['DELETE'],
            'finance.employee-salaries.index' => ['GET'],
            'finance.employee-salaries.create' => ['GET'],
            'finance.employee-salaries.store' => ['POST'],
            'finance.employee-salaries.show' => ['GET'],
            'finance.employee-salaries.edit' => ['GET'],
            'finance.employee-salaries.update' => ['PUT'],
            'finance.employee-salaries.toggle' => ['PATCH'],
            'finance.employee-salaries.destroy' => ['DELETE'],
            'finance.payroll-periods.index' => ['GET'],
            'finance.payroll-periods.create' => ['GET'],
            'finance.payroll-periods.store' => ['POST'],
            'finance.payroll-periods.show' => ['GET'],
            'finance.payroll-periods.edit' => ['GET'],
            'finance.payroll-periods.update' => ['PUT'],
            'finance.payroll-periods.destroy' => ['DELETE'],
            'finance.payroll-periods.calculate' => ['POST'],
            'finance.payroll-periods.review' => ['PATCH'],
            'finance.payroll-periods.approve' => ['PATCH'],
            'finance.payroll-periods.pay' => ['PATCH'],
            'finance.payroll-periods.close' => ['PATCH'],
            'finance.payroll-periods.reopen' => ['PATCH'],
            'finance.payrolls.index' => ['GET'],
            'finance.payrolls.show' => ['GET'],
            'finance.payrolls.slip' => ['GET'],
            'finance.reports.index' => ['GET'],
        ];

        foreach ($expectedRoutes as $name => $methods) {
            $route = app('router')->getRoutes()->getByName($name);

            self::assertInstanceOf(Route::class, $route, "Route [{$name}] tidak terdaftar.");
            self::assertSame($methods, $this->businessMethods($route), "HTTP method route [{$name}] tidak sesuai.");

            $controller = $route->getController();
            $method = $route->getActionMethod();

            self::assertTrue(
                method_exists($controller, $method),
                "Controller method [{$controller::class}@{$method}] tidak tersedia.",
            );
        }
    }

    private function businessMethods(Route $route): array
    {
        return array_values(array_filter(
            $route->methods(),
            static fn (string $method): bool => $method !== 'HEAD',
        ));
    }
}
